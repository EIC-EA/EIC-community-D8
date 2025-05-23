/**
 * @file
 * Defines JavaScript jump menu for the eic_dashboards module.
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eicDashboardsJumpMenu = {
    attach: function attach(context, drupalSettings) {
      $('.js-jump-menu', context).on('change', function () {
        window.location = window.location.origin + $(this).find(':selected').data('url');
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
