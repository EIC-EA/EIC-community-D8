/**
 * @file
 * Implements the interaction logic for the collapsible options.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.collapsibleOptions = {
    attach: function () {
      const collapsibleOptions = document.querySelectorAll(
        '.ecl-collapsible-options:not(.ecl-collapsible-options--is-ready)'
      );

      if (!collapsibleOptions || !collapsibleOptions.length) {
        return;
      }

      for (let i = 0; i < collapsibleOptions.length; i++) {
        defineCollapsibleOptions(collapsibleOptions[i]);
      }
    },
  };

  /**
   * Implements the logic for the defined collapsibleOptions element.
   */
  function defineCollapsibleOptions(collapsibleOptions) {
    const collapsibleOptionsTrigger = collapsibleOptions.querySelectorAll(
      '.ecl-collapsible-options__trigger'
    );

    const collapsibleOptionsCollapseWrapper = collapsibleOptions.querySelector(
      '.ecl-collapsible-options__collapse-wrapper'
    );

    if (!collapsibleOptionsTrigger || !collapsibleOptionsTrigger.length) {
      return;
    }

    for (let i = 0; i < collapsibleOptionsTrigger.length; i++) {
      defineCollapsibleOptionsTrigger(collapsibleOptionsTrigger[i], collapsibleOptions);
    }

    document.addEventListener('keydown', (event) => {
      // Only accept the escape key.
      if (!event.keyCode || event.keyCode !== 27) {
        return;
      }

      collapse(collapsibleOptions);
    });

    document.addEventListener('click', (event) => {
      if (event.target === collapsibleOptions || collapsibleOptions.contains(event.target)) {
        return;
      }

      collapse(collapsibleOptions);
    });

    collapsibleOptions.setAttribute('aria-expanded', 'false');

    collapsibleOptions.classList.add('ecl-collapsible-options--is-ready');
  }

  /**
   * Implements the collapse logic for the defined collapsibleOptionsTrigger
   * element.
   */
  function defineCollapsibleOptionsTrigger(collapsibleOptionsTrigger, collapsibleOptions) {
    collapsibleOptionsTrigger.setAttribute('aria-visible', 'true');

    collapsibleOptionsTrigger.addEventListener('click', (event) => {
      toggle(collapsibleOptions);

      event.preventDefault();
    });
  }

  function toggle(collapsibleOptions) {
    if (collapsibleOptions.getAttribute('aria-expanded') == 'true') {
      collapse(collapsibleOptions);
    } else {
      expand(collapsibleOptions);
    }
  }

  function collapse(collapsibleOptions) {
    collapsibleOptions.setAttribute('aria-expanded', 'false');
  }

  function expand(collapsibleOptions) {
    collapsibleOptions.setAttribute('aria-expanded', 'true');
  }
})(ECL, Drupal);
