/**
 * @file
 * Implements the interaction logic for the tooltip as progressiveenhancement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.tooltip = {
    attach: function () {
      const tooltip = document.querySelectorAll('.ecl-tooltip:not(.ecl-tooltip--is-ready)');

      if (!tooltip || !tooltip.length) {
        return;
      }

      for (let i = 0; i < tooltip.length; i++) {
        defineTooltip(tooltip, i);
      }
    },
  };

  /**
   * Implements the logic for the current tooltip HTMLElement.
   *
   * @param {NodeList} context The tooltip HTMLElement collection.
   * @param {Number} index Implements the logic from the given index within the
   * NodeList.
   */
  function defineTooltip(context, index) {
    const tooltip = context[index];
    const tooltipContent = tooltip.querySelector('.ecl-tooltip__content');
    const tooltipCorner = tooltip.querySelector('.ecl-tooltip__panel-corner');
    const tooltipToggle = tooltip.querySelector('.ecl-tooltip__toggle');
    const tooltipClose = tooltip.querySelector('.ecl-tooltip__close');
    const tooltipHelper = tooltip.querySelector('.ecl-tooltip__helper');

    if (!tooltipContent || !tooltipToggle) {
      return;
    }

    let throttle;

    document.addEventListener('keydown', (event) => {
      // Only accept the [esc] key.
      if (!event.keyCode || event.keyCode !== 27) {
        return;
      }

      collapse(tooltip);
    });

    document.addEventListener('click', (event) => {
      if (
        event.target === tooltipToggle ||
        tooltipContent.contains(event.target) ||
        tooltipClose.contains(event.target) ||
        tooltipToggle.contains(event.target)
      ) {
        return;
      }

      collapse(tooltip);
    });

    tooltipClose.addEventListener('click', (event) => {
      event.preventDefault();

      collapse(tooltip, context);
    });

    tooltipToggle.addEventListener('click', (event) => {
      event.preventDefault();

      toggle(tooltip, context);
      positionDropdown(tooltip, tooltipContent, tooltipCorner, tooltipHelper);
    });

    window.addEventListener('resize', () => {
      throttle && clearTimeout(throttle);

      throttle = setTimeout(
        () => positionDropdown(tooltip, tooltipContent, tooltipCorner, tooltipHelper),
        200
      );
    });

    tooltip.setAttribute('aria-expanded', 'false');

    tooltip.classList.add('ecl-tooltip--is-ready');

    positionDropdown(tooltip, tooltipContent, tooltipCorner, tooltipHelper);
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
  const collapse = (node) => node && node.setAttribute('aria-expanded', 'false');

  /**
   * Expands the current collapsibleOptions and collapse all the other
   * instances.
   *
   * @param {HTMLElement} collapsibleOptions The current element that will
   * expand.
   * @param {NodeList} context Collapses all other elements.
   */
  const expand = (node, context) => {
    node.setAttribute('aria-expanded', 'true');

    // Only 1 dropdown can be expanded.
    for (let i = 0; i < context.length; i++) {
      if (context[i] !== node) {
        collapse(context[i]);
      }
    }
  };

  const positionDropdown = (context, target, corner, helper) => {
    target.style.top = null;
    corner.style.top = null;
    target.style.maxWidth = null;

    if (!helper || !helper.offsetParent) {
      return;
    }

    const { y } = context.getBoundingClientRect();

    if (y > 0) {
      if (y > context.scrollHeight / 2 + context.offsetHeight / 2) {
        target.style.top = `${0 - target.scrollHeight / 2 + context.offsetHeight / 2}px`;
        corner.style.top = `${target.scrollHeight / 2 + context.offsetHeight / 2}px`;
      } else {
        target.style.top = `${0 - y}px`;
        corner.style.top = `${y}px`;
      }
    }

    if (context.parentElement && context.parentElement.offsetWidth) {
      target.style.maxWidth = `${context.parentElement.offsetWidth / 2 - context.offsetWidth}px`;
    }
  };
})(ECL, Drupal);
