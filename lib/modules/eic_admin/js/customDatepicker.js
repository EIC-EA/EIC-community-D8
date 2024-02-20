(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customDateFormatter = {
    attach: function attach(context, settings) {
      // todo add once
      $('.js-datepicker-custom').each(function () {
        $(this).pickadate({
          format: 'dd/mm/yyyy',
          formatSubmit: 'yyyy-mm-dd',
          hiddenName: true,
          container: $(this).closest('.js-form-wrapper'),
        });
      })
    }
  }

})(jQuery, Drupal, drupalSettings);
