/**
 * @file
 * Implements the logic for the featuredContentSection HTMLElements.
 */
(function (Drupal) {
  Drupal.behaviors.featuredContentCollection = {
    attach: function () {
      const featuredContentCollection = document.querySelectorAll(
        '.ecl-featured-content-collection:not(.ecl-featured-content-collection--is-ready)'
      );

      if (!featuredContentCollection.length) {
        return;
      }

      for (let i = 0; i < featuredContentCollection.length; i++) {
        defineFeaturedContentCollection(featuredContentCollection[i]);
      }
    },
  };

  function defineFeaturedContentCollection(featuredContentCollection) {
    defineCollapsibleFeaturedContentCollection(featuredContentCollection);

    featuredContentCollection.classList.add('ecl-featured-content-collection--is-ready');
  }

  function defineCollapsibleFeaturedContentCollection(featuredContentCollection) {
    if (
      !featuredContentCollection.classList.contains(
        'ecl-featured-content-collection--is-collapsible'
      )
    ) {
      return;
    }

    const featuredContentCollectionExpand = featuredContentCollection.querySelector(
      '.ecl-featured-content-collection__expand'
    );

    if (!featuredContentCollectionExpand) {
      return;
    }

    featuredContentCollection.setAttribute('aria-collapsed', 'true');

    featuredContentCollectionExpand.addEventListener('click', (event) => {
      event.preventDefault();

      featuredContentCollection.setAttribute('aria-collapsed', 'false');
    });
  }
})(Drupal);
