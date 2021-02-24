/**
 * @file
 * Implements the interaction logic for the filter sidebar as progressiveenhancement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.expandableDefinitions = {
    attach: function () {
      const expandableDefinitions = document.querySelectorAll('.ecl-expandable-definitions');

      if (!expandableDefinitions || !expandableDefinitions.length) {
        return;
      }

      for (let i = 0; i < expandableDefinitions.length; i++) {
        defineExpandableDefinitions(expandableDefinitions[i]);
      }
    },
  };

  /**
   * Inserts the logic for the current expandableDefinitions HTMLElement.
   */
  function defineExpandableDefinitions(expandableDefinitions) {
    const expandableDefinitionsItem = expandableDefinitions.querySelectorAll(
      '.ecl-expandable-definitions__item:not(.ecl-expandable-definitions__item--is-ready)'
    );

    if (!expandableDefinitionsItem || !expandableDefinitionsItem.length) {
      return;
    }

    for (let i = 0; i < expandableDefinitionsItem.length; i++) {
      defineExpandableDefinitionsItem(expandableDefinitionsItem[i]);
    }
  }

  /**
   * Inserts the expandable logic for each new subitem.
   */
  function defineExpandableDefinitionsItem(expandableDefinitionsItem) {
    const expandableDefinitionsSubitems = expandableDefinitionsItem.querySelector(
      '.ecl-expandable-definitions__item-subitems'
    );
    const expandableDefinitionsSubitem = expandableDefinitionsSubitems.querySelectorAll(
      '.ecl-expandable-definitions__item-subitem'
    );

    if (!expandableDefinitionsSubitem || !expandableDefinitionsSubitem.length) {
      return;
    }

    if (expandableDefinitionsSubitem.length === 1) {
      return;
    }

    const expandableDefinitionsMore = expandableDefinitionsItem.querySelector(
      '.ecl-expandable-definitions__item-more'
    );

    if (!expandableDefinitionsMore) {
      return;
    }

    expandableDefinitionsSubitems.setAttribute('aria-hidden', 'true');

    expandableDefinitionsMore.addEventListener('click', (event) => {
      event.preventDefault();

      expandableDefinitionsSubitems.removeAttribute('aria-hidden');
    });

    expandableDefinitionsItem.classList.add('ecl-expandable-definitions__item--is-ready');
  }
})(ECL, Drupal);
