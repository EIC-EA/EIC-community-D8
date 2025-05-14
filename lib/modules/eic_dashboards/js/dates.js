(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.dateFilters = {
    attach: function attach(context) {
      // ---- Listing pages from/to placeholder
      $('.listing-page .form-item-created-min input', context).attr('placeholder', Drupal.t('From'));
      $('.listing-page .form-item-created-max input', context).attr('placeholder', Drupal.t('To'));
      $('.listing-page .form-item-access-min input', context).attr('placeholder', Drupal.t('From'));
      $('.listing-page .form-item-access-max input', context).attr('placeholder', Drupal.t('To'));
      $('.listing-page .form-item-field-project-date-start-min input', context).attr('placeholder', Drupal.t('From'));
      $('.listing-page .form-item-field-project-date-start-max input', context).attr('placeholder', Drupal.t('To'));
      $('.listing-page .form-item-field-project-date-end-min input', context).attr('placeholder', Drupal.t('From'));
      $('.listing-page .form-item-field-project-date-end-max input', context).attr('placeholder', Drupal.t('To'));
    }
  };
})(jQuery, Drupal, drupalSettings);


