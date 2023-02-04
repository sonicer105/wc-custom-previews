<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

/**
 * Class WC_CP_API
 *
 * All the REST API code
 */
class WC_CP_API {

    public static string $namespace = 'wc-custom-previews/v1';

    /**
     * WC_CP_API constructor.
     */
    function __construct() {
        add_action('rest_api_init', function () {
            register_rest_route(self::$namespace, '/generate', array(
                'methods' => 'GET',
                'callback' => [$this, 'GenerateImage'],
                'permission_callback' => '__return_true'
            ));
        });
    }

    /**
     * @param WP_REST_Request $request
     */
    function GenerateImage($request) {
        $this->CheckUA();

        $layer_config = [];

        if(!isset($request['id']) || empty($request['id'])){
            return new WP_Error('no-post-id', 'Post ID is required', array('status' => 400));
        }

        $config = WC_CP_Admin_UI::get_config_for_product($request['id']);

        if ($config instanceof WP_Error) return $config;

        foreach ($config['layers'] as $layer) {
            $blend_mode = isset($layer['blendMode']) ? $layer['blendMode'] : Imagick::COMPOSITE_DEFAULT;
            $blend_channel = isset($layer['blendChannel']) ? $layer['blendChannel'] : Imagick::CHANNEL_DEFAULT;
            $color_code = $layer['colorConfigurable'] ? $this->GetSanitizedColor($request, $layer['id']) : '';
            $src_id = $layer['srcConfigurable'] ? $this->GetSanitizedSrcId($request, $layer['id']) : $layer['src'];
            if ($src_id instanceof WP_Error) return $src_id;
            array_push($layer_config, new WP_WC_Image_Layer(
                wp_get_original_image_path($src_id),
                $color_code,
                new WP_WC_Image_Size_And_Location(
                    $layer['offsetX'],
                    $layer['offsetY'],
                    $layer['width'],
                    $layer['height']
                ),
                $blend_mode,
                $blend_channel
            ));
        }

        $image = $this->CompositeImage($layer_config);
        // Brighten image to compensate for dark color sample.
        $image->brightnessContrastImage(11, 8);
        header('Content-Type: image/' . $image->getImageFormat());
        echo $image;
        die();
    }

    /**
     * Function takes an array of ImageLayer config objects.
     * This will composite each image in the order of the array index.
     * @param $layer_config
     * @return mixed
     */
    function CompositeImage($layer_config) {
        if (empty($layer_config)) {
            die('Cannot paint an empty image.');
        }
        $image = $layer_config[0]->generate_layer();
        for ($i = 1; $i < count($layer_config); $i++) {
            $image->compositeImage($layer_config[$i]->generate_layer(), $layer_config[$i]->blend_mode(), $layer_config[$i]->size_and_position()->x_offset(), $layer_config[$i]->size_and_position()->y_offset(), $layer_config[$i]->blend_channel());
        }
        return $image;
    }

    /**
     * Fetches a sanitized 6-digit hexadecimal from the GET parameter.
     * Defaults to #FFFFFF if parameter is invalid or unavailable.
     * @param $request WP_REST_Request
     * @param $key
     * @return string
     */
    function GetSanitizedColor($request, $key) {
        $color = '#FFFFFF';
        $temp = $request[$key];
        if (strlen($temp) == 7) $temp = substr($temp, 1);
        if (ctype_xdigit($temp) && strlen($temp) == 6) {
            $color = '#' . htmlspecialchars($request[$key]);
        }
        return $color;
    }

    /**
     * Fetches a sanitized image attachment ID, provided it's valid in this context.
     * @param $request WP_REST_Request
     * @param $key
     * @return number | WP_Error
     */
    function GetSanitizedSrcId($request, $key) {
        $src = intval($request[$key]);
        if(get_post_status($src)){
            return $src;
        }
        return new WP_Error('invalid-image-id', $key . ' is not valid', array('status' => 400));
    }

    function CheckUA() {
        $user_agents = ['discord', 'slack', 'telegram', 'facebook', 'whatsapp', 'googlebot', 'apis-google', 'snap url preview', 'kik'];
        foreach($user_agents as $agent) {
            if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), $agent) !== false) {
                header('Content-Type: text/html');
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Agent Blocked</title>
    <meta content="User Agent Blocked" property="og:title" />
    <meta content="We don't have the processing power to allow embedding of dynamically generated images. Please don't post links to them! Please download and re-upload the image instead." property="og:description" />
    <meta content="https://avalistore.com/" property="og:url" />
    <meta content="#ff0000" data-react-helmet="true" name="theme-color" />
</head>
<body>We don't have the processing power to allow embedding of dynamically generated images. Please don't post links to them! Please download and re-upload the image instead.</body>
</html><?php
                die();
            }
        }
    }
}

/**
 * Class ImageLayer
 */
class WP_WC_Image_Layer {
    // File path to layer image.
    public $image_path = '';
    // Hexadecimal color to apply to the layer.
    public $color_code = '';
    // Offset and sizing to use
    public $size_and_position = null;
    // Blend mode to use.
    public $blend_mode = Imagick::COMPOSITE_DEFAULT;
    // Blend channel to use
    public $blend_channel = Imagick::CHANNEL_DEFAULT;

    public function __construct($image_path, $color_code, $size_and_position, $blend_mode, $blend_channel) {
        $this->image_path = $image_path;
        $this->color_code = $color_code;
        $this->size_and_position = $size_and_position;
        $this->blend_mode = $blend_mode;
        $this->blend_channel = $blend_channel;
    }

    public function generate_layer() {
        $layer = new Imagick($this->image_path);
        $layer->transformImageColorspace(Imagick::COLORSPACE_SRGB);
        $layer->setImageBackgroundColor('transparent');
        $layer->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        if (!empty($this->color_code)) {
            $layer->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);
            $layer->opaquePaintImage('black', $this->color_code, 60000, false);
            $layer->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        }
        if ($this->size_and_position->width() >= 0) {
            $requested_width = $this->size_and_position->width();
            $requested_height = $this->size_and_position->height();

            $layer->resizeImage($requested_width, $requested_width, Imagick::FILTER_LANCZOS, 1, true);

            $actual_width = $layer->getImageWidth();
            $actual_height = $layer->getImageHeight();

            $off_top = 0;
            $off_left = 0;

            if ($actual_height < $requested_height) {
                $off_top = (($requested_height - $actual_height) / 2) * -1;
            } else if ($actual_width < $requested_width) {
                $off_left = (($requested_width - $actual_width) / 2) * -1;
            }

            if ($off_top !== 0 || $off_left !== 0) {
                $layer->extentImage($requested_width, $requested_height, $off_left, $off_top);
            }
        }
        return $layer;
    }

    public function size_and_position()
    {
        return $this->size_and_position;
    }

    public function blend_mode()
    {
        return $this->blend_mode;
    }

    public function blend_channel()
    {
        return $this->blend_channel;
    }
}

class WP_WC_Image_Size_And_Location {
    public $x_offset = 0;
    public $y_offset = 0;
    public $width = -1;
    public $height = -1;

    public function __construct($x_offset = 0, $y_offset = 0, $width = -1, $height = -1) {
        $this->x_offset = $x_offset;
        $this->y_offset = $y_offset;
        $this->width = $width;
        $this->height = $height;

        if($this->width >= 0 && $this->height < 0) $this->height = $this->width;
        if($this->height >= 0 && $this->width < 0) $this->width = $this->height;
    }

    public function x_offset() {
        return $this->x_offset;
    }

    public function y_offset() {
        return $this->y_offset;
    }

    public function width() {
        return $this->width;
    }

    public function height() {
        return $this->height;
    }
}

$wc_cp_api = new WC_CP_API();