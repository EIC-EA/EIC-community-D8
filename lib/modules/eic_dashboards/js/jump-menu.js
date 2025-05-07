/**
 * @file
 * Defines JavaScript jump menu for the eic_dashboards module.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eicDashboardsJumpMenu = {
    attach: function attach(context, drupalSettings) {
      var path = drupalSettings.path;
      $('.js-jump-menu', context).on('change', function () {
        window.location = path.baseUrl + $(this).find(':selected').data('url').replace(/^\//, "");
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
