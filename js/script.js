/**
 * @typedef colorPickerPallet
 * @type {Object[]}
 * @property {string} id Identifier
 * @property {string} title Nice name for displaying
 * @property {string} value The hex color code including pound symbol
 */

// Wait for page load
(function ($){

    let still_init = true;

    /**
     * Initialize Color Picker on single product page
     */
    function initColorPicker() {
        // Detect if color picker was loaded yet
        if($().colorPick){
            // Init the Color Picker
            $(".color-picker").colorPick({
                'initialColor': '#ffffff',
                'onColorSelected': onColorSelected,
                'allowRecent': true,
                'recentMax': 5,
                'palette': colorPickerPallet.map(x => x.value),
                'paletteLabel': 'Available Fabrics',
                'allowCustomColor': false,
            })
            init_preview_pane();
            still_init = false;
            console.log('Color Picker ready!');
        } else {
            // Back off for 250ms if it was not detected and try again
            setTimeout(initColorPicker, 250);
        }
    }

    /**
     * Color Picker color selected callback
     */
    function onColorSelected() {
        // set the target's value in the `data-for` field
        $($(this.element).data('for')).val(this.color);

        // set the little color block's color
        $(this.element).find('.color-preview').css({'backgroundColor': this.color});

        // set the preview text to the color's hex code
        $(this.element).find('.color-text').text(this.color);

        if (!still_init) {
            show_preview();
        }
    }

    function init_preview_pane() {
        //$(".woocommerce-product-gallery").hide();
        $(".woocommerce-product-gallery").append('<button id="switch-to-preview" class="button alt">Show preview</button>')
        $("#switch-to-preview").on('click', show_preview)
        $("#switch-to-gallery").on('click', hide_preview)
        $(".avali-image-preview").hide();
        $(".avali-image-preview img").on('load', function () {
            $(".avali-image-preview .loader-wrapper").hide();
        });
    }

    function show_preview() {
        $(".woocommerce-product-gallery").hide();
        $(".avali-image-preview").show();
        refresh_preview();
    }

    function hide_preview() {
        $(".woocommerce-product-gallery").show();
        $(".avali-image-preview").hide();
    }

    function refresh_preview() {
        let fields = $('form.cart').serializeArray();
        let url = new URL(window.location.origin + '/wp-json/wc-custom-previews/v1/generate');
        for (let i in fields){
            if(fields.hasOwnProperty(i)) {
                url.searchParams.append(fields[i].name, fields[i].value.substring(1))
            }
        }
        $(".avali-image-preview img").prop('src', url.href);
        $(".avali-image-preview .loader-wrapper").show();
    }

    // Do nothing if we are not on the avali product page
    $(function() {
        if($('.avali-product').length > 0) {
            initColorPicker();
        }
    });
})(jQuery);