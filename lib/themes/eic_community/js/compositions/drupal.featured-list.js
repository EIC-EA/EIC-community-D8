/**
 * @file
 * Implements the logic for the featuredList HTMLElements.
 */
(function (Drupal) {
  Drupal.behaviors.featuredList = {
    attach: function () {
      const featuredList = document.querySelectorAll(
        '.ecl-featured-list:not(.ecl-featured-list--is-ready)'
      );

      if (!featuredList.length) {
        return;
      }

      for (let i = 0; i < featuredList.length; i++) {
        defineFeaturedList(featuredList[i]);
      }
    },
  };

  /**
   * Implements the collapse logic for the current featuredList element.
   *
   * @param {HTMLElement} featuredList
   */
  function defineFeaturedList(featuredList) {
    if (!featuredList.classList.contains('ecl-featured-list--is-collapsible')) {
      return;
    }

    const featuredListExpand = featuredList.querySelector('.ecl-featured-list__expand');
    const featuredListCollapse = featuredList.querySelector('.ecl-featured-list__collapse');

    if (!featuredListExpand) {
      return;
    }

    featuredList.setAttribute('aria-collapsed', 'true');

    featuredListExpand.addEventListener('click', (event) => {
      event.preventDefault();

      featuredList.setAttribute('aria-collapsed', 'false');
    });

    if (featuredListCollapse) {
      featuredListCollapse.addEventListener('click', (event) => {
        event.preventDefault();

        featuredList.setAttribute('aria-collapsed', 'true');
      });
    }

    featuredList.classList.add('ecl-featured-list--is-ready');
  }
})(Drupal);
