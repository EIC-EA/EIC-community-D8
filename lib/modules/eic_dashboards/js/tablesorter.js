(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eicDashboardsTablesorter = {
    attach: function attach(context) {
      if ($('main', context).length > 0) {
        $(window).on('load', function () {
          $(".js-stories-table").tablesorter();
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
