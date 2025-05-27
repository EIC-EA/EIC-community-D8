(function ($, Drupal) {
  Drupal.behaviors.listingDateRangeHandler = {
    attach: function (context, settings) {
            const fields = [
        'created',
        'access',
        'field-project-date-start',
        'field-project-date-end'
      ];

      // Set placeholders
      fields.forEach(function (field) {
        $(`.listing-page .form-item-${field}-min input`, context).attr('placeholder', Drupal.t('From'));
        $(`.listing-page .form-item-${field}-max input`, context).attr('placeholder', Drupal.t('To'));
      });

      // On form submit, validate min/max dates
      $('form', context).on('submit', function (e) {
        fields.forEach(function (field) {
          const $min = $(`.listing-page .form-item-${field}-min input`, context);
          const $max = $(`.listing-page .form-item-${field}-max input`, context);

          const minVal = $min.val();
          const maxVal = $max.val();

          if (minVal && !maxVal) {
            $max.val('2050-01-01');
          } else if (!minVal && maxVal) {
            $min.val('1980-01-01');
          }
        });
      });
    }
  };
})(jQuery, Drupal);
