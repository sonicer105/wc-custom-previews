<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

/**
 * Class WC_CP_API
 *
 * All the REST API code
 */
class WC_CP_API {

    /**
     * WC_CP_API constructor.
     */
    function __construct() {
        add_action('rest_api_init', function () {
            register_rest_route( 'wc-custom-previews/v1', '/generate', array(
                'methods' => 'GET',
                'callback' => [$this, 'GenerateImage'],
            ));
        });
    }

    /**
     * @param WP_REST_Request $request
     */
    function GenerateImage($request) {
        $layer_config = [];

        /*
        'id' => 'primary',
        'title' => 'Primary',
        'src' => WC_CP_PATH . 'img/0_primary.png',
        'configurable' => true
         */

        foreach (WC_Custom_Previews::layer_config['layers'] as $layer) {
            $blend_mode = isset($layer['blend_mode']) ? $layer['blend_mode'] : Imagick::COMPOSITE_DEFAULT;
            $blend_channel = isset($layer['blend_channel']) ? $layer['blend_channel'] : Imagick::CHANNEL_DEFAULT;
            if($layer['configurable']) {
                array_push($layer_config, new WP_WC_Image_Layer($layer['src'], $this->GetSanitizedColor($request, $layer['id']), $blend_mode, $blend_channel));
            } else {
                array_push($layer_config, new WP_WC_Image_Layer($layer['src'], '', $blend_mode, $blend_channel));
            }
        }

        $image = $this->CompositeImage($layer_config);
        header('Content-Type: image/' . $image->getImageFormat());
        echo $image;
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
            $image->compositeImage($layer_config[$i]->generate_layer(), $layer_config[$i]->blend_mode(), 0, 0, $layer_config[$i]->blend_channel());
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
}

/**
 * Class ImageLayer
 */
class WP_WC_Image_Layer {
    // File path to layer image.
    public $image_path = '';
    // Hexadecimal color to apply to the layer.
    public $color_code = '';
    // Blend mode to use.
    public $blend_mode = Imagick::COMPOSITE_DEFAULT;
    // Blend channel to use
    public $blend_channel = Imagick::CHANNEL_DEFAULT;

    public function __construct($image_path, $color_code, $blend_mode, $blend_channel) {
        $this->image_path = $image_path;
        $this->color_code = $color_code;
        $this->blend_mode = $blend_mode;
        $this->blend_channel = $blend_channel;
    }

    public function generate_layer() {
        $layer = new Imagick($this->image_path);
        $layer->setImageBackgroundColor('transparent');
        $layer->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        if (!empty($this->color_code)) {
            $layer->setImageAlphaChannel(Imagick::ALPHACHANNEL_DEACTIVATE);
            $layer->opaquePaintImage('black', $this->color_code, 30000, false);
            $layer->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        }
        return $layer;
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

$wc_cp_api = new WC_CP_API();