(function($){
  function setPreview($img, url){
    if (url) { $img.attr('src', url).show(); } else { $img.hide(); }
  }
  $(document).on('click', '.huadev-media-button', function(e){
    e.preventDefault();
    var targetId = $(this).data('target');
    var previewId = $(this).data('preview');
    var $input = $('#' + targetId);
    var $preview = $('#' + previewId);

    var frame = wp.media({
      title: 'Select image',
      button: { text: 'Use this image' },
      multiple: false
    });

    frame.on('select', function(){
      var attachment = frame.state().get('selection').first().toJSON();
      $input.val(attachment.url).trigger('change');
      setPreview($preview, attachment.url);
    });

    frame.open();
  });

  $('#preset_seal_url').on('input change', function(){ setPreview($('#preset_seal_url_preview'), this.value); });
  $('#preset_image_url').on('input change', function(){ setPreview($('#preset_image_url_preview'), this.value); });
})(jQuery);
