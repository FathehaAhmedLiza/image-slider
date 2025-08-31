(function($){
    $(function(){
        var $list = $('#sis-image-list');
        $list.sortable({
            placeholder: 'sis-placeholder',
            update: syncImagesJson
        });

        function syncImagesJson(){
            var items = [];
            $list.find('.sis-image-item').each(function(){
                items.push({
                    id: parseInt($(this).attr('data-id'), 10),
                    caption: $(this).find('.sis-caption').val() || ''
                });
            });
            $('#sis-images-json').val(JSON.stringify(items));
        }

        $list.on('input', '.sis-caption', syncImagesJson);

        $('#sis-add-images').on('click', function(e){
            e.preventDefault();
            var frame = wp.media({
                title: 'Select Images',
                button: { text: 'Add to Slider' },
                multiple: true
            });
            frame.on('select', function(){
                var selection = frame.state().get('selection');
                selection.each(function(attachment){
                    attachment = attachment.toJSON();
                    var thumb = attachment.sizes && (attachment.sizes.thumbnail || attachment.sizes.medium || attachment.sizes.full);
                    var $li = $('<li class="sis-image-item" data-id="'+attachment.id+'">\
                        <img src="'+(thumb ? thumb.url : '')+'" alt=""/>\
                        <input type="text" class="widefat sis-caption" placeholder="Caption (optional)" />\
                        <button class="button-link-delete sis-remove">&times;</button>\
                    </li>');
                    $list.append($li);
                });
                $list.sortable('refresh');
                syncImagesJson();
            });
            frame.open();
        });

        $list.on('click', '.sis-remove', function(e){
            e.preventDefault();
            $(this).closest('.sis-image-item').remove();
            syncImagesJson();
        });
    });
})(jQuery);
