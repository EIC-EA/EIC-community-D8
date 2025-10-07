(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.toggleNonRequiredOnDraft = {
    attach: function (context) {
      // If required is checked AND eic_non_required_on_draft is also checked,
      // uncheck eic_non_required_on_draft.
      $('input[name=required]').on('change', function () {
        if ($(this).prop('checked') && $('input[name=eic_non_required_on_draft]').prop('checked')) {
          $('input[name=eic_non_required_on_draft]').prop('checked', false);
        }
      });

      // If eic_non_required_on_draft is checked AND require is also checked,
      // uncheck require.
      $('input[name=eic_non_required_on_draft]').on('change', function () {
        if ($(this).prop('checked') && $('input[name=required]').prop('checked')) {
          $('input[name=required]').prop('checked', false);
        }
      });
    }
  };

}(jQuery, Drupal));
