/**
 * @file
 * Implements the interaction logic for each global header HTMLElement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.languageSelector = {
    attach: function () {
      const languageSelector = document.querySelectorAll(
        '.ecl-language-selector:not(.ecl-language-selector--is-ready)'
      );

      if (!languageSelector || !languageSelector.length) {
        return;
      }

      for (let i = 0; i < languageSelector.length; i++) {
        defineLanguageSelector(languageSelector[i]);
      }
    },
  };

  /**
   * Implements the language selector logic for the current globalHeader
   * HTMLElement.
   */
  function defineLanguageSelector(languageSelector) {
    const languageSelectorLink = languageSelector.querySelector('.ecl-language-selector__link');

    const languageSelectorLanguageList = languageSelector.querySelector(
      '.ecl-language-selector__language-list'
    );

    if (!languageSelectorLink || !languageSelectorLanguageList) {
      return;
    }

    languageSelectorLink.addEventListener('click', (event) => {
      event.preventDefault();

      expand(languageSelectorLanguageList);
    });

    // Close the overlay with the [esc] key.
    document.addEventListener('keydown', (event) => {
      if (!event.keyCode || event.keyCode !== 27) {
        return;
      }

      collapse(languageSelectorLanguageList);
    });

    // Implement close overlay for the close button.
    const languageSelectorLanguageListClose = languageSelectorLanguageList.querySelector(
      '.ecl-language-list__close-button'
    );

    if (languageSelectorLanguageListClose) {
      languageSelectorLanguageListClose.addEventListener('click', (event) => {
        event.preventDefault();

        collapse(languageSelectorLanguageList);
      });
    }

    languageSelector.classList.add('ecl-language-selector--is-ready');
  }

  /**
   * Hides the language selector overlay.
   *
   * @param {HTMLElement} context
   */
  const collapse = (context) => {
    context && context.setAttribute('hidden', true);
  };

  /**
   * Shows the language selector overlay.
   *
   * @param {HTMLElement} context
   */
  const expand = (context) => {
    context && context.removeAttribute('hidden');
  };
})(ECL, Drupal);
