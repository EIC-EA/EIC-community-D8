/**
 * @file
 * overides ECL function
 */
(function (ECL, Drupal) {
  Drupal.behaviors.defineECL = {
    attach: function attach() {
      overideMainMenuEvent();
      initializeDatepicker();
    },
  };

  function overideMainMenuEvent() {
    const menu = document.querySelector('.ecl-mainmenu__main');
    const menuInner = document.querySelector('.ecl-menu__inner');

    document.querySelector('.ecl-menu__open').addEventListener('click', function (e) {
      e.preventDefault();
      menu.setAttribute('aria-expanded', true);
      menuInner.setAttribute('aria-expanded', true);
    });
    document.querySelector('.ecl-menu__close').addEventListener('click', function (e) {
      e.preventDefault();
      menu.removeAttribute('aria-expanded');
      menuInner.removeAttribute('aria-expanded');
    });
  }

  // Initializes all the datepicker and ensures that the initialisation is triggered once per element.
  function initializeDatepicker() {
    var datepickerElements = document.querySelectorAll('[data-ecl-datepicker-toggle]');

    datepickerElements.forEach(function(elt) {
      if (!elt.getAttribute('data-datepicker-initialized')) {
        var datepicker = new ECL.Datepicker(elt, {format: 'YYYY-MM-DD'});
        datepicker.init();
        elt.setAttribute('data-datepicker-initialized', 'true');
      }
    });
  }

})(ECL, Drupal);
