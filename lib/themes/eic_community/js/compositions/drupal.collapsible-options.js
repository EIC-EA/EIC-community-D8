/**
 * @file
 * Implements the interaction logic for the collapsible options.
 */
(function (Drupal) {
  Drupal.behaviors.collapsibleOptions = {
    attach: function () {
      const collapsibleOptions = document.querySelectorAll(
        '.ecl-collapsible-options:not(.ecl-collapsible-options--is-ready)'
      );

      if (!collapsibleOptions || !collapsibleOptions.length) {
        return;
      }

      for (let i = 0; i < collapsibleOptions.length; i++) {
        defineCollapsibleOptions(collapsibleOptions, i);
      }
    },
  };

  /**
   * Implements the logic for the defined collapsibleOptions element.
   *
   * @param {NodeList} context The instance collection.
   * @param {Number} index Implements the logic from the given index within the
   * NodeList.
   */
  function defineCollapsibleOptions(context, index) {
    const collapsibleOptions = context[index];
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
      defineCollapsibleOptionsTrigger(collapsibleOptionsTrigger[i], collapsibleOptions, context);
    }

    document.addEventListener('keydown', (event) => {
      // Only accept the [esc] key.
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

    collapsibleOptions.style.display = 'inline-block';

    collapsibleOptions.setAttribute('aria-expanded', 'false');

    collapsibleOptions.classList.add('ecl-collapsible-options--is-ready');
  }

  /**
   * Implements the collapse logic for the defined collapsibleOptionsTrigger
   * element.
   *
   * @param {HTMLElement} collapsibleOptionsTrigger Binds the interaction events
   * to the selected trigger element.
   * @param {HTMLElement} collapsibleOptions The selected collapsibleOptions
   * instance that will use the logic.
   * @param {NodeList} context Collapses all other elements after the selected
   * instance expands.
   */
  function defineCollapsibleOptionsTrigger(collapsibleOptionsTrigger, collapsibleOptions, context) {
    collapsibleOptionsTrigger.setAttribute('aria-visible', 'true');

    // Fixes double focus issue for interactive elements within the trigger.
    if (collapsibleOptionsTrigger.firstElementChild) {
      collapsibleOptionsTrigger.firstElementChild.setAttribute('tabindex', -1);
    }

    collapsibleOptionsTrigger.addEventListener('click', (event) => {
      event.preventDefault();

      toggle(collapsibleOptions, context);
    });

    collapsibleOptionsTrigger.addEventListener('keydown', (event) => {
      // Only accept the [enter] key.
      if (!event.keyCode || event.keyCode !== 13) {
        return;
      }

      event.preventDefault();

      toggle(collapsibleOptions, context);
    });

    collapsibleOptionsTrigger.addEventListener('keydown', (event) => {
      // Only accept the [enter] key.
      if (!event.keyCode || event.keyCode !== 13) {
        return;
      }

      toggle(collapsibleOptions);

      event.preventDefault();
    });

    collapsibleOptionsTrigger.setAttribute('tabindex', 0);
  }

  /**
   * Toggles between the [aria-expanded] attribute for the selected
   * collapsibleOptions instance.
   *
   * @param {HTMLElement} node The current element that will toggle.
   * @param {NodeList} context Collapses all other elements after the selected
   * instance expands.
   */
  const toggle = (node, context) => {
    if (node.getAttribute('aria-expanded') == 'true') {
      collapse(node);
    } else {
      expand(node, context);
    }
  };

  /**
   * Collapses the selected collapsibleOptions instance.
   *
   * @param {HTMLElement} collapsibleOptions
   */
  const collapse = (collapsibleOptions) => {
    collapsibleOptions.setAttribute('aria-expanded', 'false');
  };

  /**
   * Expands the current collapsibleOptions and collapse all the other
   * instances.
   *
   * @param {HTMLElement} collapsibleOptions The current element that will
   * expand.
   * @param {NodeList} context Collapses all other elements.
   */
  const expand = (collapsibleOptions, context) => {
    collapsibleOptions.setAttribute('aria-expanded', 'true');

    // Only 1 dropdown can be expanded.
    for (let i = 0; i < context.length; i++) {
      if (context[i] !== collapsibleOptions) {
        collapse(context[i]);
      }
    }
  };
})(Drupal);
