(function ($, Drupal, once) {
  Drupal.behaviors.customDateFormatter = {
    attach: function attach(context) {
      const $elements = $(once('customDatepicker', '.js-datepicker-custom'));
      $elements.each(function () {
        $(this).pickadate({
          format: 'dd/mm/yyyy',
          formatSubmit: 'yyyy-mm-dd',
          hiddenName: true,
          container: $(this).closest('.js-form-wrapper'),
        });
      })
    }
  }
})(jQuery, Drupal, once);
