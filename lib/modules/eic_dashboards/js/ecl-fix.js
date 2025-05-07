(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eicDashboardsECLFix = {
    attach: function attach(context, settings) {
      // Use once() to ensure this code only runs once per element
      $(once('ecl-select-init', '[data-ecl-select-multiple]', context)).each(function() {
        ECL.Select.autoInit(this);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);


