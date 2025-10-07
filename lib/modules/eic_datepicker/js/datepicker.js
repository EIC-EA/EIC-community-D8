(function ($, Drupal, once) {
  Drupal.behaviors.datepicker = {
    attach: function attach(context) {
      const $elements = $(once('datepickerCustom', '.js-datepicker-custom'));
      $elements.each(function () {
        $(this).pickadate({
          format: 'dd/mm/yyyy',
          formatSubmit: 'yyyy-mm-dd',
          hiddenName: true,
          container: $(this).closest('.js-form-wrapper'),
        });
        $(this).addClass('form-date');
      })
    }
  }
})(jQuery, Drupal, once);
