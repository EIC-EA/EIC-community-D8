(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.dates = {
    attach: function attach(context) {
      // Auto init multiple select.
      document.querySelectorAll('[data-ecl-select-multiple]').forEach((el) => {
        ECL.Select.autoInit(el); // You can pass options as a second parameter if needed
      });

      // ECL.autoInit();
      // ECL.Select('js-form-type-select');
      // // ---- Members list - Registered from/to
      // // Wrap 'Registered from' & 'Registered to' inside a new inner div
      // var $registeredFields = $('.form-item-registered-from, .form-item-registered-to')
      //   .wrapAll('<div class="dates-from-to"></div>') // Inner wrapper for dates
      //   .parent() // Get the newly created wrapper
      //   .wrap('<div class="date-from-to-wrapper registered-date-wrapper ecl-u-mv-m"></div>'); // Outer wrapper for everything
      // // Prepend the label inside the registered wrapper
      // $('.registered-date-wrapper').prepend('<label class="form-group-label ecl-form-label">' + Drupal.t("Registered") + '</label>');
      // $('.form-item-registered-from input', context).attr('placeholder', Drupal.t('From'));
      // $('.form-item-registered-to input', context).attr('placeholder', Drupal.t('To'));

      // ---- Members list - Last access from/to
      // Wrap 'Last access From' & 'Last access To' inside a new inner div
      // var $lastAccessFields = $('.form-item-last-access-from, .form-item-last-access-to')
      //   .wrapAll('<div class="dates-from-to"></div>') // Inner wrapper for dates
      //   .parent() // Get the newly created wrapper
      //   .wrap('<div class="date-from-to-wrapper last-access-date-wrapper ecl-u-mv-m"></div>'); // Outer wrapper for everything
      // // Prepend the label inside the last access wrapper
      // $('.last-access-date-wrapper').prepend('<label class="form-group-label ecl-form-label">' + Drupal.t("Last access") + '</label>');
      // $('.form-item-last-access-from input', context).attr('placeholder', Drupal.t('From'));
      // $('.form-item-last-access-to input', context).attr('placeholder', Drupal.t('To'));

      // ---- Content list - Published from/to
      // Wrap 'Published From' & 'Published To' inside a new inner div
      // var $publishedFields = $('.form-item-published-from , .form-item-published-to')
      //   .wrapAll('<div class="dates-from-to"></div>') // Inner wrapper for dates
      //   .parent() // Get the newly created wrapper
      //   .wrap('<div class="date-from-to-wrapper published-date-wrapper ecl-u-mv-m"></div>'); // Outer wrapper for everything
      // // Prepend the label inside the last access wrapper
      // $('.published-date-wrapper').prepend('<label class="form-group-label ecl-form-label">' + Drupal.t("Published date") + '</label>');
      // $('.form-item-published-from input', context).attr('placeholder', Drupal.t('From'));
      // $('.form-item-published-to input', context).attr('placeholder', Drupal.t('To'));
    }
  };
})(jQuery, Drupal, drupalSettings);


