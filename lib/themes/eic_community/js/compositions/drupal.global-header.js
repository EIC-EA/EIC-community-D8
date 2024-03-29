/**
 * @file
 * Implements the interaction logic for each global header HTMLElement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.globalHeader = {
    attach: function () {
      const globalHeader = document.querySelectorAll('.ecl-global-header');

      if (!globalHeader || !globalHeader.length) {
        return;
      }

      for (let i = 0; i < globalHeader.length; i++) {
        defineGlobalHeaderLanguageList(globalHeader[i]);
      }
    },
  };

  /**
   * Implements the language selector logic for the current globalHeader
   * HTMLElement.
   */
  function defineGlobalHeaderLanguageList(globalHeader) {
    const globalHeaderLanguageSelector = globalHeader.querySelector(
      '.ecl-global-header__language-selector:not(.ecl-global-header__language-selector--is-ready)'
    );

    const globalHeaderLanguageList = globalHeader.querySelector(
      '.ecl-global-header__language-list:not(.ecl-global-header__language-list--is-ready)'
    );

    if (!globalHeaderLanguageSelector || !globalHeaderLanguageList) {
      return;
    }

    globalHeaderLanguageSelector.addEventListener('click', (event) => {
      event.preventDefault();

      hide(globalHeaderLanguageList);
    });

    // Close the overlay with the [esc] key.
    document.addEventListener('keydown', (event) => {
      if (!event.keyCode || event.keyCode !== 27) {
        return;
      }

      show(globalHeaderLanguageList);
    });

    // Implement close overlay for the close button.
    const globalHeaderLanguageListClose = globalHeaderLanguageList.querySelector(
      '.ecl-language-list__close-button'
    );

    if (globalHeaderLanguageListClose) {
      globalHeaderLanguageListClose.addEventListener('click', (event) => {
        event.preventDefault();

        show(globalHeaderLanguageList);
      });
    }

    globalHeaderLanguageSelector.classList.add('ecl-global-header__language-selector--is-ready');
    globalHeaderLanguageList.classList.add('ecl-global-header__language-list--is-ready');
  }

  /**
   * Hides the selected overlay element.
   *
   * @param {HTMLElement} node
   */
  const show = (node) => {
    node && node.setAttribute('hidden', true);
  };

  /**
   * Shows the selected overlay element.
   *
   * @param {HTMLElement} node
   */
  const hide = (node) => {
    node && node.removeAttribute('hidden');
  };
})(ECL, Drupal);
