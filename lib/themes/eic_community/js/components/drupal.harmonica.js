/**
 * @file
 * Implements the interaction logic for the harmonica components as
 * progressive enhancement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.harmonica = {
    attach: function () {
      const harmonica = document.querySelectorAll('.ecl-harmonica:not(.ecl-harmonica--is-ready)');

      if (!harmonica || !harmonica.length) {
        return;
      }

      for (let i = 0; i < harmonica.length; i++) {
        defineHarmonica(harmonica[i]);
      }
    },
  };

  /**
   * Implements the interaction logic for the selected harmonica HTMLElement.
   */
  function defineHarmonica(harmonica) {
    const harmonicaItems = harmonica.querySelector('.ecl-harmonica__items');
    const { children } = harmonicaItems;

    if (!children || !children.length) {
      return;
    }

    for (let i = 0; i < children.length; i++) {
      defineHarmonicaItem(children[i]);
    }

    harmonica.classList.add('ecl-harmonica--is-ready');
  }

  /**
   * Implements the interaction logic for the select harmonic item HTMLElement.
   */
  function defineHarmonicaItem(harmonicaItem) {
    if (!harmonicaItem.classList.contains('ecl-harmonica__item')) {
      return;
    }

    if (harmonicaItem.classList.contains('ecl-harmonica__item--is-ready')) {
      return;
    }

    const harmonicaItemContent = harmonicaItem.querySelector('.ecl-harmonica__item-content');

    console.log(harmonicaItemContent);

    if (!harmonicaItemContent) {
      return;
    }

    const harmonicaItemHeader = harmonicaItem.querySelector('.ecl-harmonica__item-header');

    console.log(harmonicaItemHeader);

    if (!harmonicaItemHeader) {
      return;
    }

    harmonicaItemHeader.addEventListener('click', (event) => {
      event.preventDefault();

      toggle(harmonicaItem);
    });

    harmonicaItem.classList.add('ecl-harmonica__item--is-ready');
  }

  /**
   * Expands or collapses the selected harmonica item.
   */
  const toggle = (harmonicaItem) => {
    const ariaExpanded = harmonicaItem.getAttribute('aria-expanded');

    if (!ariaExpanded || ariaExpanded === 'false') {
      expand(harmonicaItem);
    } else {
      collapse(harmonicaItem);
    }
  };

  /**
   * Helper function to expand the defined context element.
   */
  const expand = (context) => context && context.setAttribute('aria-expanded', true);

  /**
   * Helper function to expand the defined context element.
   */
  const collapse = (context) => context && context.removeAttribute('aria-expanded');
})(ECL, Drupal);
