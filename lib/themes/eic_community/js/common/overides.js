/**
 * @file
 * overides ECL function
 */
(function (ECL, Drupal) {
  Drupal.behaviors.defineECL = {
    attach: function attach() {
      overideMainMenuEvent();
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
})(ECL, Drupal);
