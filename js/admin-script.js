"use strict";
jQuery(function ($){

    /* GRID EDITOR CODE */

    let addNewCounter = 0;
    let gridData = {}
    let gridContainer = $('#grid-table');
    if(gridContainer.length > 0){
        gridContainer.empty().append("<tbody></tbody>");
        $("#new-row-button").on('click', function (e){
            e.preventDefault();
            addNewGrid(null);
        }).insertAfter(gridContainer);
        populateExistingGrids()
        if($('#grid-type').val() === "color") {
            $('#grid-default-value').wpColorPicker();
        }
        $("#grid-form").on("submit", gridBeforeSubmit)
    }

    function addNewGrid(item) {
        item = item ?? {
            id: '',
            title: '',
            description: '',
            value: ''
        }
        gridContainer.find('tbody').first().append('<tr>' +
            '<th>' +
            '<button class="button button-secondary button-move-up">' +
            '<span class="dashicons dashicons-arrow-up-alt"></span>' +
            '</button>' +
            '<button class="button button-secondary button-move-down">' +
            '<span class="dashicons dashicons-arrow-down-alt"></span>' +
            '</button>' +
            '<button class="button button-danger button-delete">' +
            '<span class="dashicons dashicons-trash"></span>' +
            '</button>' +
            '</th>' +
            '<td>' +
            '<table class="grid-editor-wrapper"><tbody><tr>' +
            '<th><label for="row-id-' + addNewCounter + '">ID</label></th>' +
            '<td><input type="text" id="row-id-' + addNewCounter + '" class="data-id" value="' + item.id + '"></td>' +
            '</tr><tr>' +
            '<th><label for="row-title-' + addNewCounter + '">Title</label></th>' +
            '<td><input type="text" id="row-title-' + addNewCounter + '" class="data-title" value="' + item.title + '"></td>' +
            '</tr><tr>' +
            '<th><label for="row-value-' + addNewCounter + '">Value</label></th>' +
            '<td><input type="text" id="row-value-' + addNewCounter + '" class="data-value" value="' + item.value + '"></td>' +
            '</tr><tr>' +
            '<th><label for="row-description-' + addNewCounter + '">Description</label></th>' +
            '<td><input type="text" id="row-description-' + addNewCounter + '" class="data-description" value="' + item.description + '"></td>' +
            '</tr></tbody></table>' +
            '<hr>' +
            '</td>' +
            '</tr>')
        gridContainer.find('.button-delete').last().on('click', deleteGrid)
        gridContainer.find('.button-move-up').last().on('click', moveGridUp)
        gridContainer.find('.button-move-down').last().on('click', moveGridDown)
        if($('#grid-type').val() === "color") {
            $('#row-value-' + addNewCounter).wpColorPicker();
        }
        addNewCounter++;
    }

    function deleteGrid(e) {
        e.preventDefault();
        this.parentElement.parentElement.remove()
    }

    function moveGridUp(e) {
        e.preventDefault();
        if(this.parentElement.parentElement && this.parentElement.parentElement.previousElementSibling) {
            $(this.parentElement.parentElement.previousElementSibling)
                .insertAfter($(this.parentElement.parentElement))
        }
    }

    function moveGridDown(e) {
        e.preventDefault();
        if(this.parentElement.parentElement && this.parentElement.parentElement.nextElementSibling) {
            $(this.parentElement.parentElement.nextElementSibling)
                .insertBefore($(this.parentElement.parentElement))
        }
    }

    function populateExistingGrids() {
        gridData = JSON.parse($("#grid_data").val());
        for (let i in gridData.option_value.grids) {
            if (gridData.option_value.grids.hasOwnProperty(i)) {
                addNewGrid(gridData.option_value.grids[i]);
            }
        }
    }

    function gridBeforeSubmit(e) {
        gridData.option_value.title = $("#grid-title").val();
        gridData.option_value.defaultValue = $("#grid-default-value").val();
        gridData.option_value.gridType = $("#grid-type").val();
        gridData.option_value.lastEdit = new Date().toISOString();
        if(gridData.option_value.gridType === 'color') {
            gridData.option_value.defaultValue =
                gridData.option_value.defaultValue.toUpperCase();
        }
        gridData.option_value.grids = [];
        $('#grid-table > tbody > tr').each(function (i, el){
            let value = $(el).find(".data-value").first().val();
            if(gridData.option_value.gridType === 'color') {
                value = value.toUpperCase();
            }
            gridData.option_value.grids[i] = {
                id: $(el).find(".data-id").first().val(),
                title: $(el).find(".data-title").first().val(),
                value: value,
                description: $(el).find(".data-description").first().val()
            }
        })

        gridData.option_value = JSON.stringify(gridData.option_value);
        $("#grid_data").val(JSON.stringify(gridData));
    }

    /* END OF GRID EDITOR CODE */

    /* PREVIEW EDITOR CODE */

    const Imagick = {
        COMPOSITE_DEFAULT: 40,
        COMPOSITE_MULTIPLY: 38,
        CHANNEL_DEFAULT: 134217719,
        CHANNEL_ALPHA: 8
    }

    let layerData = {}
    let layerContainer = $('#layer-table');
    if(layerContainer.length > 0){
        layerContainer.empty().append("<tbody></tbody>");
        $("#new-row-button").on('click', function (e){
            e.preventDefault();
            addNewLayer(null);
        }).insertAfter(layerContainer);
        populateExistingLayers()
        $("#layer-form").on("submit", layerBeforeSubmit)
    }

    function addNewLayer(item) {
        item = item ?? {
            id: '',
            title: '',
            srcConfigurable: false,
            src: "",
            colorConfigurable: false,
            color: "",
            blendChannel: Imagick.COMPOSITE_DEFAULT,
            blendMode: Imagick.CHANNEL_DEFAULT
        }
        let colorOptions = '<option value=""' + (item.color === '' ? ' selected="selected"' : '') + '>none</option>'
        let srcOptions = '<option value=""' + (item.src === '' ? ' selected="selected"' : '') + '>none</option>'
        for (let i in WC_CP_GRIDS) {
            if(WC_CP_GRIDS.hasOwnProperty(i)) {
                if (WC_CP_GRIDS[i].gridType === 'color') {
                    colorOptions += '<option value="' + WC_CP_GRIDS[i].id + '"' + (item.color === WC_CP_GRIDS[i].id ? ' selected="selected"' : '') + '>' + WC_CP_GRIDS[i].title + '</option>'
                } else if (WC_CP_GRIDS[i].gridType === 'src') {
                    srcOptions += '<option value="' + WC_CP_GRIDS[i].id + '"' + (item.src === WC_CP_GRIDS[i].id ? ' selected="selected"' : '') + '>' + WC_CP_GRIDS[i].title + '</option>'
                }
            }
        }
        let blendChannelOptions = '<option value="' + Imagick.CHANNEL_DEFAULT + '"' + (item.blendChannel === Imagick.CHANNEL_DEFAULT ? ' selected="selected"' : '') + '>CHANNEL_DEFAULT</option>' +
            '<option value="' + Imagick.CHANNEL_ALPHA + '"' + (item.blendChannel === Imagick.CHANNEL_ALPHA ? ' selected="selected"' : '') + '>CHANNEL_ALPHA</option>'
        let blendModeOptions = '<option value="' + Imagick.COMPOSITE_DEFAULT + '"' + (item.blendMode === Imagick.COMPOSITE_DEFAULT ? ' selected="selected"' : '') + '>COMPOSITE_DEFAULT</option>' +
            '<option value="' + Imagick.COMPOSITE_MULTIPLY + '"' + (item.blendMode === Imagick.COMPOSITE_MULTIPLY ? ' selected="selected"' : '') + '>COMPOSITE_MULTIPLY</option>'
        layerContainer.find('tbody').first().append('<tr>' +
            '<th>' +
            '<button class="button button-secondary button-move-up">' +
            '<span class="dashicons dashicons-arrow-up-alt"></span>' +
            '</button>' +
            '<button class="button button-secondary button-move-down">' +
            '<span class="dashicons dashicons-arrow-down-alt"></span>' +
            '</button>' +
            '<button class="button button-danger button-delete">' +
            '<span class="dashicons dashicons-trash"></span>' +
            '</button>' +
            '</th>' +
            '<td>' +
            '<table class="layer-editor-wrapper"><tbody><tr>' +
            '<th><label for="row-id-' + addNewCounter + '">ID</label></th>' +
            '<td><input type="text" id="row-id-' + addNewCounter + '" class="data-id" value="' + item.id + '"></td>' +
            '</tr><tr>' +
            '<th><label for="row-title-' + addNewCounter + '">Title</label></th>' +
            '<td><input type="text" id="row-title-' + addNewCounter + '" class="data-title" value="' + item.title + '"></td>' +
            '</tr><tr>' +
            '<th><label for="row-src-configurable-' + addNewCounter + '">Src Configurable</label></th>' +
            '<td><input type="checkbox" id="row-src-configurable-' + addNewCounter + '" class="data-src-configurable"' + (item.srcConfigurable ? ' checked="checked"' : '') + '></td>' +
            '</tr><tr>' +
            '<th><label for="row-src-' + addNewCounter + '">Src</label></th>' +
            '<td>' +
            '<div class="image-preview-wrapper">' +
            '<img id="row-src-' + addNewCounter + '-preview" src="" width="100" height="100" style="max-height: 100px; width: 100px;">' +
            '</div>' +
            '<input type="text" id="row-src-' + addNewCounter + '" class="data-src" readonly="readonly" value="' + item.src + '">' +
            '<input id="row-src-' + addNewCounter + '-button" type="button" class="button button-src" value="Set" />' +
            '</td>' +
            '</tr><tr>' +
            '<th><label for="row-color-configurable-' + addNewCounter + '">Color Configurable</label></th>' +
            '<td><input type="checkbox" id="row-color-configurable-' + addNewCounter + '" class="data-color-configurable"' + (item.colorConfigurable ? ' checked="checked"' : '') + '></td>' +
            '</tr><tr>' +
            '<th><label for="row-color-' + addNewCounter + '">Color</label></th>' +
            '<td><select id="row-color-' + addNewCounter + '" class="data-color">' + colorOptions + '</td>' +
            '</tr><tr>' +
            '<th><label for="row-blend-channel-' + addNewCounter + '">Blend Channel</label></th>' +
            '<td><select id="row-blend-channel-' + addNewCounter + '" class="data-blend-channel">' + blendChannelOptions + '</td>' +
            '</tr><tr>' +
            '<th><label for="row-blend-mode-' + addNewCounter + '">Blend Mode</label></th>' +
            '<td><select id="row-blend-mode-' + addNewCounter + '" class="data-blend-mode">' + blendModeOptions + '</td>' +
            '</tr></tbody></table>' +
            '<hr>' +
            '</td>' +
            '</tr>')
        layerContainer.find('.button-delete').last().on('click', deleteLayer)
        layerContainer.find('.button-move-up').last().on('click', moveLayerUp)
        layerContainer.find('.button-move-down').last().on('click', moveLayerDown)
        layerContainer.find('.button-src').last().on('click', layerMediaPicker)
        if(item.src && item.src.toString().length > 0) {
            loadImageAsync(item.src, '#row-src-' + addNewCounter + '-preview');
        }
        addNewCounter++;
    }

    function loadImageAsync(attachment, target){
        if (!wp.media.attachment(attachment).get('url')) {
            wp.media.attachment(attachment).fetch().then(function () {
                $(target).attr('src', wp.media.attachment(attachment).get('url')).css( 'width', 'auto' );
            });
        } else {
            $(target).attr('src', wp.media.attachment(attachment).get('url')).css( 'width', 'auto' );
        }
    }

    function layerMediaPicker(e) {
        e.preventDefault();
        let base_id = $(this).attr('id').substr(0, $(this).attr('id').length - 7);

        // Uploading files
        let file_frame;
        let wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
        let set_to_post_id = $('#base_id').val();
        console.log();

        // If the media frame already exists, reopen it.
        if (file_frame) {
            // Set the post ID to what we want
            file_frame.uploader.uploader.param('post_id', set_to_post_id);
            // Open frame
            file_frame.open();
            return;
        } else {
            // Set the wp.media post id so the uploader grabs the ID we want when initialised
            wp.media.model.settings.post.id = set_to_post_id;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select a image to upload',
            button: {
                text: 'Use this image',
            },
            multiple: false	// Set to true to allow multiple files to be selected
        });

        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
            // We set multiple to false so only get one image from the uploader
            let attachment = file_frame.state().get('selection').first().toJSON();

            // Do something with attachment.id and/or attachment.url here
            $( '#' + base_id + '-preview' ).attr( 'src', attachment.url ).css( 'width', 'auto' );
            $( '#' + base_id ).val( attachment.id );

            // Restore the main post ID
            wp.media.model.settings.post.id = wp_media_post_id;
        });

        // Finally, open the modal
        file_frame.open();
    }

    function deleteLayer(e) {
        e.preventDefault();
        this.parentElement.parentElement.remove()
    }

    function moveLayerUp(e) {
        e.preventDefault();
        if(this.parentElement.parentElement && this.parentElement.parentElement.previousElementSibling) {
            $(this.parentElement.parentElement.previousElementSibling)
                .insertAfter($(this.parentElement.parentElement))
        }
    }

    function moveLayerDown(e) {
        e.preventDefault();
        if(this.parentElement.parentElement && this.parentElement.parentElement.nextElementSibling) {
            $(this.parentElement.parentElement.nextElementSibling)
                .insertBefore($(this.parentElement.parentElement))
        }
    }

    function populateExistingLayers() {
        layerData = JSON.parse($("#layer_data").val());
        for (let i in layerData.option_value.layers) {
            if (layerData.option_value.layers.hasOwnProperty(i)) {
                addNewLayer(layerData.option_value.layers[i]);
            }
        }
    }

    function layerBeforeSubmit(e) {
        layerData.option_value.title = $("#preview-title").val();
        layerData.option_value.lastEdit = new Date().toISOString();
        layerData.option_value.layers = [];
        $('#layer-table > tbody > tr').each(function (i, el){
            layerData.option_value.layers[i] = {
                id: $(el).find(".data-id").first().val(),
                title: $(el).find(".data-title").first().val(),
                srcConfigurable: $(el).find(".data-src-configurable").first().prop("checked"),
                src: $(el).find(".data-src").first().val(),
                colorConfigurable: $(el).find(".data-color-configurable").first().prop("checked"),
                color: $(el).find(".data-color").first().val(),
                blendChannel: parseInt($(el).find(".data-blend-channel").first().val()),
                blendMode: parseInt($(el).find(".data-blend-mode").first().val()),
            }
        })

        layerData.option_value = JSON.stringify(layerData.option_value);
        $("#layer_data").val(JSON.stringify(layerData));
    }

    /* END OF PREVIEW EDITOR CODE */
});
console.log("loaded");