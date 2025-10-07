/**
 * @file
 * Provides the processing logic for tabs.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.NonRequiredOnDraftTabs = {
    attach: function (context) {
      // Add required fields mark to any element containing required fields.
      var direction = 'horizontal';
      $(context).find('[data-' + direction + '-tabs-panes]').each(function () {
        var errorFocussed = false;
        $(once('fieldgroup-effects', $(this).find('> details'))).each(function () {
          var $this = $(this);
          if (typeof $this.data(direction + 'Tab') !== 'undefined') {
            if ($this.find('.form-non-required-on-draft').length > 0) {
              $this.data(direction + 'Tab').link.find('strong:first').addClass('form-non-required-on-draft');
            }
          }
        });
      });
    }
  };

}(jQuery, Drupal));
