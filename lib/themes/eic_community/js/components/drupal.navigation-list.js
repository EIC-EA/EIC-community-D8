/**
 * @file
 * Implements the interaction logic for the filter sidebar as progressiveenhancement.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.navigationList = {
    attach: function () {
      const navigationList = document.querySelectorAll('.ecl-navigation-list');

      if (!navigationList || !navigationList.length) {
        return;
      }

      for (let i = 0; i < navigationList.length; i++) {
        defineNavigationList(navigationList[i]);
      }
    },
  };

  /**
   * Implements the logic for the current navigation list HTMLElement.
   */
  function defineNavigationList(navigationList) {
    const navigationListItem = navigationList.querySelectorAll(
      '.ecl-navigation-list__item:not(.ecl-navigation-list__item--is-ready)'
    );

    if (navigationListItem.length) {
      for (let i = 0; i < navigationListItem.length; i++) {
        defineNavigationListItem(navigationListItem[i]);
      }
    }

    defineNavigationListCollapse(navigationList);

    navigationList.classList.add('ecl-navigation-list--is-ready');
  }

  /**
   * Inserts the collapse logic for each navigation list item HTMLElement.
   */
  function defineNavigationListItem(navigationListItem) {
    const navigationListLink = navigationListItem.querySelector('.ecl-navigation-list__link');

    if (!navigationListLink) {
      return;
    }

    navigationListLink.addEventListener('click', (event) => {
      if (!navigationListItem.querySelectorAll('.ecl-navigation-list__item').length) {
        return;
      }

      if (navigationListItem.parentElement) {
        Array.from(navigationListItem.parentElement.children)
          .filter((el) => el !== navigationListItem)
          .filter((el) => el.classList.contains('ecl-navigation-list__item'))
          .forEach((el) => el.removeAttribute('aria-active'));
      }

      const active = navigationListItem.getAttribute('aria-active');

      if (active && String(active).toLowerCase() == 'true') {
        return;
      }

      navigationListItem.setAttribute('aria-active', 'true');

      event.preventDefault();
    });

    navigationListItem.classList.add('ecl-navigation-list__item--is-ready');
  }

  /**
   * Updates the session storage object to keep track of the state changes.
   */
  function writeSession(props) {
    if (window.sessionStorage && window.sessionStorage.setItem) {
      const commit = window.sessionStorage.setItem(
        'ecl-navigation-list',
        JSON.stringify(Object.assign(readSession(), props || {}))
      );
    }
  }

  /**
   * Read the session to restore the initial state.
   */
  function readSession(name) {
    if (!window.sessionStorage || !window.sessionStorage.getItem) {
      return;
    }

    const commit = JSON.parse(window.sessionStorage.getItem('ecl-navigation-list')) || {};

    if (!commit) {
      return;
    }

    if (name && commit[name] != null) {
      return commit[name];
    }

    return commit;
  }

  /**
   * Implements the collapse logic for the current navigation list HTMLElement.
   */
  function defineNavigationListCollapse(navigationList) {
    const navigationListToggle = navigationList.querySelector(
      '.ecl-navigation-list__toggle:not(.ecl-navigation-list__toggle--is-ready)'
    );

    if (!navigationListToggle) {
      return;
    }

    navigationListToggle.addEventListener('click', (event) => {
      event.preventDefault();

      const collapsed = navigationList.getAttribute('aria-collapsed');

      if (collapsed && String(collapsed).toLowerCase() === 'true') {
        navigationList.removeAttribute('aria-collapsed');
        writeSession({
          collapsed: false,
        });
        navigationList.dispatchEvent(new Event('toggle'));
      } else {
        navigationList.setAttribute('aria-collapsed', 'true');
        writeSession({
          collapsed: true,
        });
        navigationList.dispatchEvent(new Event('toggle'));
      }

      collapseParentElement(navigationList);
    });

    if (readSession('collapsed') === true) {
      navigationList.setAttribute('aria-collapsed', 'true');
      collapseParentElement(navigationList);
    }

    navigationListToggle.classList.add('ecl-navigation-list__toggle--is-ready');
  }

  function collapseParentElement(navigationList) {
    const parentSelector = navigationList.getAttribute('data-navigation-list-parent-selector');

    if (!parentSelector || !document.querySelector(parentSelector)) {
      return;
    }

    // Don't query the parent element everytime.
    if (!navigationList.parentSelector) {
      navigationList.parentSelector = getParent(
        navigationList,
        document.querySelector(parentSelector)
      );
    }

    if (!navigationList.parentSelector) {
      return;
    }

    navigationList.parentSelector.dispatchEvent(new Event('toggle'));

    if (navigationList.getAttribute('aria-collapsed') === 'true') {
      navigationList.parentSelector.setAttribute('aria-collapsed', 'true');
    } else {
      navigationList.parentSelector.removeAttribute('aria-collapsed');
    }
  }

  /**
   * Returns the Parent element from the given HTMLElement context.
   *
   * @param {HTMLElement} context Use the parent element from the defined context.
   * @param {HTMLElement} target The element that should exist as parent from the context.
   */
  const getParent = (context, target) => {
    let node = context;

    if (node !== null) {
      do {
        if (node === target) {
          return node;
        }
      } while ((node = node.parentNode));
    }
  };
})(ECL, Drupal);
