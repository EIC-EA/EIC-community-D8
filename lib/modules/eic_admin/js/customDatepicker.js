(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.customDateFormatter = {
    attach: function attach() {

      $(drupalSettings.elementId).pickadate({
        format: 'dd/mm/yyyy',
        formatSubmit: 'yyyy-mm-dd',
        hiddenName: true,
        container: $(drupalSettings.elementId).closest('.js-form-wrapper'),
      });

    }
  }
  // todo pass date formats automatically from hook_preprocess
  // todo fix drupalSettings.elementId

})(jQuery, Drupal, drupalSettings);
