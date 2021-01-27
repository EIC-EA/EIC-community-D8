/**
 * @file
 * Initiates the ECL component library
 */
(function (ECL, Drupal) {
  Drupal.behaviors.defineECL = {
    attach: function () {
      const filterSidebar = document.querySelectorAll(
        '.ecl-filter-sidebar:not(.ecl-filter-sidebar--is-ready)'
      );

      if (!filterSidebar || !filterSidebar.length) {
        return;
      }

      for (let i = 0; i < filterSidebar.length; i++) {
        defineFilterSidebar(filterSidebar[i]);
      }
    },
  };

  /**
   * Assigns the logic for the current filterSidebar composition.
   */
  function defineFilterSidebar(filterSidebar) {
    const filterSidebarItem = filterSidebar.querySelectorAll('.ecl-filter-sidebar__item');
    const filterSidebarExpand = filterSidebar.querySelector('.ecl-filter-sidebar__expand');
    const filterSidebarCollapse = filterSidebar.querySelector('.ecl-filter-sidebar__collapse');

    for (let i = 0; i < filterSidebarItem.length; i++) {
      defineFilterSidebarItem(filterSidebarItem[i]);
    }

    // Implements expand feature for current filterSidebar element.
    filterSidebarExpand.addEventListener('click', function (event) {
      filterSidebar.setAttribute('aria-expanded', 'true');
    });

    // Implements expand feature for current filterSidebar element.
    filterSidebarCollapse.addEventListener('click', function (event) {
      filterSidebar.setAttribute('aria-expanded', 'false');
    });

    filterSidebar.classList.add('ecl-filter-sidebar--is-ready');
  }

  /**
   * Assigns the logic for each filterSidebarItem within the filterSidebar.
   */
  function defineFilterSidebarItem(filterSidebarItem) {
    if (!filterSidebarItem.classList.contains('ecl-filter-sidebar__item--is-collapsible')) {
      return;
    }

    const filterSidebarItemForm = filterSidebarItem.querySelector('.ecl-filter-sidebar__item-form');
    const filterSidebarItemLabel = filterSidebarItem.querySelector(
      '.ecl-filter-sidebar__item-label'
    );

    const filterSidebarItemCollapse = filterSidebarItem.querySelector(
      '.ecl-filter-sidebar__item-collapse'
    );
    const filterSidebarItemExpand = filterSidebarItem.querySelector(
      '.ecl-filter-sidebar__item-expand'
    );

    function toggleItem(event) {
      event.preventDefault();

      if (filterSidebarItem.getAttribute('aria-collapsed') == 'true') {
        filterSidebarItem.setAttribute('aria-collapsed', 'false');
      } else {
        filterSidebarItem.setAttribute('aria-collapsed', 'true');
      }
    }

    function expand(event) {
      event.preventDefault();

      filterSidebarItemForm.setAttribute('aria-expanded', 'true');

      filterSidebarItemCollapse.focus();
    }

    function collapse(event) {
      event.preventDefault();

      filterSidebarItemForm.setAttribute('aria-expanded', 'false');

      filterSidebarItemExpand.focus();
    }

    // Implements expand & collapse methods for the current filter item.
    filterSidebarItemLabel.addEventListener('click', toggleItem);
    filterSidebarItemLabel.addEventListener(
      'keyup',
      (event) => event.keyCode === 13 && toggleItem(event)
    );

    // Implements the expand methods for the current filter fields.
    filterSidebarItemExpand.addEventListener('click', expand);

    // Implements the collapse methods for the current filter fields.
    filterSidebarItemCollapse.addEventListener('click', collapse);

    validateSidebarItemFields(filterSidebarItem);
  }

  /**
   * Implements the collapse & expand buttons for the selected filterSidebarItem.
   */
  function validateSidebarItemFields(filterSidebarItem) {
    const filterSidebarItemForm = filterSidebarItem.querySelector('.ecl-filter-sidebar__item-form');
    const filterSidebarItemField = filterSidebarItem.querySelectorAll(
      '.ecl-filter-sidebar__item-field'
    );

    if (filterSidebarItemField.length > 5) {
      filterSidebarItemForm.classList.add('ecl-filter-sidebar__item-form--is-expandable');
    } else {
      filterSidebarItemForm.classList.remove('ecl-filter-sidebar__item-form--is-expandable');
    }
  }
})(ECL, Drupal);
