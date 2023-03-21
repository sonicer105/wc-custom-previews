/**
 * TODO: Update Typedef
 * @/typedef colorPickerPallet
 * @/type {Object[]}
 * @/property {string} id Identifier
 * @/property {string} title Nice name for displaying
 * @/property {string} value The hex color code including pound symbol
 * @/property {string} description Additional information about the choice for display
 */

// Wait for page load
(function ($){

    /**
     * Initialize Color Picker on single product page
     */
    function initColorPicker() {
        // Detect if color picker was loaded yet
        if($().select2){
            $(".color-picker").each(function () {
                $(this).select2({
                    width: '100%',
                    data: select2GetChoices($(this).data('grid')),
                    templateResult: select2FormatStateDropdownColor,
                    templateSelection: select2FormatStateSelectedColor,
                    matcher: select2MatchCustom
                }).val(colorPickerPallet[$(this).data('grid') + '-default']).trigger('change').on("select2:select", on_dropdown_select);
            });
            $(".src-picker").each(function () {
                let allowNone = $(this).data('allowNone');
                let defaultVal = (allowNone) ? '0' : colorPickerPallet[$(this).data('grid') + '-default'];
                $(this).select2({
                    width: '100%',
                    data: select2GetChoices($(this).data('grid'), allowNone),
                    templateResult: select2FormatStateDropdownSrc,
                    templateSelection: select2FormatStateSelectedSrc,
                    matcher: select2MatchCustom
                }).val(defaultVal).trigger('change').on("select2:select", on_dropdown_select);
            });
            init_preview_pane();
            console.log('Color Picker (Select2) ready!');
        } else {
            // Back off for 250ms if it was not detected and try again
            setTimeout(initColorPicker, 250);
        }
    }

    function select2GetChoices(grid, allowNone = false) {
        let toReturn = [];
        if(allowNone){
            toReturn[0] = {
                id: '0',
                text: 'None',
                description: '',
            }
        }
        for (let i in colorPickerPallet[grid]) {
            if (colorPickerPallet[grid].hasOwnProperty(i)) {
                toReturn[toReturn.length] = {
                    id: colorPickerPallet[grid][i].value,
                    text: colorPickerPallet[grid][i].title + ' (' + colorPickerPallet[grid][i].value + ')',
                    description: colorPickerPallet[grid][i].description
                }
                if(colorPickerPallet[grid][i].src) toReturn[toReturn.length - 1].src = colorPickerPallet[grid][i].src;
            }
        }
        console.log(toReturn);
        return toReturn;
    }

    function select2FormatStateSelectedColor(state) {
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

    function select2FormatStateSelectedSrc(state) {
        if (!state.id || state.id === "0") {
            return state.text;
        }
        return $(
            '<span>' +
            '<img class="color-preview color-selected" src="' + state.src + '">' +
            '<span class="color-text">' + state.text + '</span>' +
            '</span>'
        );
    }

    function select2FormatStateDropdownColor(state) {
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

    function select2FormatStateDropdownSrc(state) {
        if (!state.id || state.id === "0") {
            return state.text;
        }
        return $(
            '<span>' +
            '<img class="color-preview color-dropdown" src="' + state.src + '" style="background-color:transparent;">' +
            '<span class="color-text">' + state.text + '<br />' +
            '<span class="color-description">' + state.description + '</span>' +
            '</span></span>'
        );
    }

    function select2MatchCustom(params, data) {
        if ($.trim(params.term) === '') { // No Search Term
            return data;
        }
        if ((typeof data.text !== 'undefined' && data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) ||
            (typeof data.description !== 'undefined' && data.description.toLowerCase().indexOf(params.term.toLowerCase()) > -1)) {
            return data;
        }
        return null;
    }

    function init_preview_pane() {
        //$(".woocommerce-product-gallery").hide();
        $(".woocommerce-product-gallery").append('<button id="switch-to-preview" class="button alt">Show preview</button>')
        $("#switch-to-preview").on('click', show_preview)
        $("#switch-to-gallery").on('click', hide_preview)
        $(".custom-previews-image-preview").hide();
        $(".custom-previews-image-preview img").on('load', function () {
            $(".custom-previews-image-preview .loader-wrapper").hide();
        });
    }

    function on_dropdown_select() {
        if($(this).val() !== "0"){
            let toUnset = $(this).data('unset-id');
            if(toUnset && toUnset.length && toUnset.length > 0){
                toUnset = toUnset.split(',');
                toUnset.forEach(function(item){
                    $('#' + item).val('0').trigger('change');
                });
            }
        }
        show_preview();
    }

    function show_preview() {
        $(".woocommerce-product-gallery").hide();
        $(".custom-previews-image-preview").show();
        refresh_preview();
    }

    function hide_preview() {
        $(".woocommerce-product-gallery").show();
        $(".custom-previews-image-preview").hide();
    }

    function refresh_preview() {
        let fields = $('form.cart').serializeArray();
        let url = new URL(window.location.origin + '/wp-json/wc-custom-previews/v1/generate');
        url.searchParams.append('id', colorPickerPallet['id'])
        for (let i in fields){
            if(fields.hasOwnProperty(i)) {
                let val = fields[i].value.startsWith('#') ? fields[i].value.substring(1) : fields[i].value;
                url.searchParams.append(fields[i].name, val)
            }
        }
        $(".custom-previews-image-preview img").prop('src', url.href);
        $(".custom-previews-image-preview .loader-wrapper").show();
    }

    // Do nothing if we are not on the custom-previews product page
    $(function() {
        if($('.custom-previews-product').length > 0) {
            initColorPicker();
        }
    });
})(jQuery);