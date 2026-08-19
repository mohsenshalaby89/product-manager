jQuery(function($) {
    function renderGalleryPreview(preview, selectedIds) {
        if (!preview || !preview.length) {
            return;
        }

        if (!selectedIds.length) {
            preview.html('');
            return;
        }

        var html = '';
        $.each(selectedIds, function(index, imageId) {
            if (!imageId) {
                return;
            }

            html += '<span class="product-manager-gallery-item"><img src="' + wp.media.attachment(imageId).attributes.url + '" alt="Gallery image" /></span>';
        });

        preview.html(html);
    }

    function openMediaSelector(options) {
        var frame = wp.media({
            title: options.title || 'Select Image',
            button: { text: options.buttonText || 'Use this image' },
            multiple: !!options.multiple
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            var selectedIds = [];

            selection.each(function(attachment) {
                var model = attachment.toJSON();
                if (model && model.id) {
                    selectedIds.push(String(model.id));
                }
            });

            if (!selectedIds.length) {
                return;
            }

            if (options.hiddenInput && options.hiddenInput.length) {
                options.hiddenInput.val(selectedIds.join(','));
            }

            if (options.preview && options.preview.length && options.multiple) {
                var previewHtml = '';
                selectedIds.forEach(function(id) {
                    var attachment = wp.media.attachment(id);
                    if (attachment && attachment.attributes && attachment.attributes.url) {
                        previewHtml += '<span class="product-manager-gallery-item"><img src="' + attachment.attributes.url + '" alt="Gallery image" style="max-width:80px;height:auto;" /></span>';
                    }
                });

                options.preview.html(previewHtml);
            }

            if (options.preview && options.preview.length && !options.multiple) {
                var singleAttachment = selection.first().toJSON();
                if (singleAttachment && singleAttachment.url) {
                    options.preview.html('<img src="' + singleAttachment.url + '" alt="Selected image" style="max-width:150px;height:auto;" />');
                }
            }
        });

        frame.open();
    }

    var featuredButton = $('#select-featured-image');
    var featuredRemoveButton = $('#remove-featured-image');
    var featuredPreview = $('#featured-image-preview');
    var featuredHiddenInput = $('#product_featured_image_id');

    if (featuredButton.length && featuredPreview.length && featuredHiddenInput.length) {
        function renderFeaturedPreview(attachment) {
            if (attachment && attachment.id) {
                featuredPreview.html('<div class="product-manager-featured-image"><img src="' + attachment.url + '" alt="Featured image" /></div>');
                featuredRemoveButton.prop('disabled', false);
                return;
            }

            featuredPreview.html('<p class="description">No featured image selected.</p>');
            featuredRemoveButton.prop('disabled', true);
        }

        featuredButton.on('click', function(event) {
            event.preventDefault();
            openMediaSelector({
                title: 'Select Featured Image',
                buttonText: 'Use this image',
                hiddenInput: featuredHiddenInput,
                preview: featuredPreview,
                multiple: false
            });
        });

        if (featuredRemoveButton.length) {
            featuredRemoveButton.on('click', function(event) {
                event.preventDefault();
                featuredHiddenInput.val('0');
                renderFeaturedPreview(null);
            });
        }
    }

    $('[data-product-manager-media]').on('click', function(event) {
        event.preventDefault();

        var target = $(this).data('target');
        var previewId = $(this).data('preview');
        var hiddenInput = $('#' + target);
        var preview = $('#' + previewId);

        if (!hiddenInput.length) {
            return;
        }

        openMediaSelector({
            title: 'Select Image',
            buttonText: 'Use this image',
            hiddenInput: hiddenInput,
            preview: preview,
            multiple: false
        });
    });

    $('[data-product-manager-gallery]').on('click', function(event) {
        event.preventDefault();

        var hiddenInput = $('#product_gallery');
        var preview = $('#product-gallery-preview');

        if (!hiddenInput.length || !preview.length) {
            return;
        }

        openMediaSelector({
            title: 'Select Product Images',
            buttonText: 'Add images',
            hiddenInput: hiddenInput,
            preview: preview,
            multiple: true
        });
    });
});
