/**
 * @typedef colorPickerPallet
 * @type {Object[]}
 * @property {string} id Identifier
 * @property {string} title Nice name for displaying
 * @property {string} value The hex color code including pound symbol
 * @property {string} description Additional information about the choice for display
 */

// Wait for page load
(function ($){

    /**
     * Initialize Color Picker on single product page
     */
    function initColorPicker() {
        // Detect if color picker was loaded yet
        if($().select2){
            $(".color-picker").select2({
                width: '100%',
                data: select2GetChoices(),
                templateResult: select2FormatStateDropdown,
                templateSelection: select2FormatStateSelected
            }).val('#FFFFFF').trigger('change').on("select2:select", show_preview);
            init_preview_pane();
            console.log('Color Picker (Select2) ready!');
        } else {
            // Back off for 250ms if it was not detected and try again
            setTimeout(initColorPicker, 250);
        }
    }

    function select2GetChoices() {
        let toReturn = [];
        for (let i in colorPickerPallet) {
            if (colorPickerPallet.hasOwnProperty(i)) {
                toReturn[i] = {
                    id: colorPickerPallet[i].value,
                    text: colorPickerPallet[i].title + ' (' + colorPickerPallet[i].value + ')',
                    description: colorPickerPallet[i].description
                }
            }
        }
        return toReturn;
    }

    function select2FormatStateSelected(state) {
        if (!state.id) {
            return state.text;
        }
        return $(
            '<span>' +
            '<span class="color-preview color-selected" style="background-color: ' + state.id + ';"></span>' +
            '<span class="color-text">' + state.text + '</span>' +
            '</span>'
        );
    }

    function select2FormatStateDropdown(state) {
        if (!state.id) {
            return state.text;
        }
        return $(
            '<span>' +
            '<span class="color-preview color-dropdown" style="background-color: ' + state.id + ';"></span>' +
            '<span class="color-text">' + state.text + '<br />' +
            '<span class="color-description">' + state.description + '</span>' +
            '</span></span>'
        );
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