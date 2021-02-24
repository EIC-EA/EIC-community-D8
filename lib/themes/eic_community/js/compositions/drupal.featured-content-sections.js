/**
 * @file
 * Implements the logic for the featuredContentSection HTMLElements.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.featuredContentSections = {
    attach: function () {
      const featuredContentSections = document.querySelectorAll(
        '.ecl-featured-content-sections:not(.ecl-featured-content-sections--is-ready)'
      );

      if (!featuredContentSections.length) {
        return;
      }

      for (let i = 0; i < featuredContentSections.length; i++) {
        defineFeaturedContentSections(featuredContentSections[i]);
      }
    },
  };

  /**
   * Assigns the required Event Listeners for the current
   * featuredContentSections element.
   *
   * The logic should only be fired if the link elements are actually visible.
   *
   * @param {HTMLElement} featuredContentSections Binds the logic from the
   * defined featuredContentSections element.
   */
  function defineFeaturedContentSections(featuredContentSections) {
    const items = featuredContentSections.querySelectorAll('.ecl-featured-content-sections__item');

    if (!items.length) {
      return;
    }

    const links = featuredContentSections.querySelectorAll('.ecl-featured-content-sections__link');

    featuredContentSections.lastKnownScrollPosition = window.scrollY || 0;

    if (links[0].offsetParent) {
      defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links);
    }

    document.addEventListener('scroll', () => {
      featuredContentSections.throttle && clearTimeout(featuredContentSections.throttle);

      if (links[0].offsetParent) {
        featuredContentSections.throttle = setTimeout(
          () => defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links),
          20
        );
      }
    });

    window.addEventListener('resize', () => {
      featuredContentSections.throttle && clearTimeout(featuredContentSections.throttle);

      if (links[0].offsetParent) {
        featuredContentSections.throttle = setTimeout(
          () => defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links),
          200
        );
      }
    });

    featuredContentSections.classList.add('ecl-featured-content-sections--is-ready');
  }

  /**
   * Marks the visible section container & anchor link with the
   * [aria-current] attribute. The last element will be selected if the
   * scrollTop value is higher than the current featuredContentSections root
   * element.
   *
   * @param {HTMLElement} featuredContentSections Binds the logic from the
   * defined featuredContentSections element.
   * @param {NodeList} items Should contain the item containers for the defined
   * featuredContentSections element.
   * @param {NodeList} links Contains the anchor links that should refer to the
   * defined items argument.
   */
  function defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links) {
    const { bounds } = featuredContentSections;
    const { scrollY, innerHeight } = window;
    const visible = [];
    let direction = '';
    let index = 0;

    for (let i = 0; i < items.length; i++) {
      items[i].removeAttribute('aria-current', true);

      if (isVisible(items[i])) {
        visible.push(items[i]);
      }
    }

    // Downwards scroll.
    if (scrollY > featuredContentSections.lastKnownScrollPosition) {
      direction = 'down';

      // Force the last item to be active after the scrollTop is larger than
      // it's offset position.
      if (visible.length >= 2 && items[items.length - 1] === visible[visible.length - 1]) {
        const { top, height } = visible[visible.length - 1].getBoundingClientRect();

        if (scrollY + innerHeight >= scrollY + top + height) {
          index = visible.length - 1;
        }
      }
    }

    // Should implement Upwards scroll condition:
    //
    // if (scrollY < featuredContentSections.lastKnownScrollPosition) {
    //   ...
    // }

    // Also Mark the actual section.
    visible[index].setAttribute('aria-current', true);

    // Mark the section link that should link to the visible section.
    if (links.length && visible.length) {
      for (let i = 0; i < links.length; i++) {
        const href = links[i].getAttribute('href');

        links[i].removeAttribute('aria-current');

        if (featuredContentSections.querySelector(href) === visible[index]) {
          links[i].setAttribute('aria-current', true);
        }
      }
    }

    // Cache the current scroll position to determine the direction for the next
    // scroll event.
    featuredContentSections.lastKnownScrollPosition = scrollY;
  }

  /**
   * Checks if at least one point of the defined HTMLElement is visible within
   * the viewport.
   *
   * @param {HTMLElement} el The element that will be checked.
   */
  const isVisible = (elem) => {
    if (!elem.offsetParent) {
      return;
    }

    const { top, left, right, bottom } = elem.getBoundingClientRect();
    const { offsetWidth, offsetHeight } = elem;

    const points = {
      center: {
        x: left + offsetWidth / 2,
        y: top + offsetHeight / 2,
      },
      topLeft: {
        x: left,
        y: top,
      },
      topRight: {
        x: right,
        y: top,
      },
      bottomLeft: {
        x: left,
        y: bottom,
      },
      bottomRight: {
        x: right,
        y: bottom,
      },
    };

    const width = document.documentElement.clientWidth || window.innerWidth;
    const height = document.documentElement.clientHeight || window.innerHeight;

    if (points.topLeft.x > width) return;
    if (points.topLeft.y > height) return;
    if (points.bottomRight.x < 0) return;
    if (points.bottomRight.y < 0) return;

    for (let key in points) {
      const point = points[key];
      let node = document.elementFromPoint(point.x, point.y);

      if (node !== null) {
        do {
          if (node === elem) return true;
        } while ((node = node.parentNode));
      }
    }

    return;
  };
})(ECL, Drupal);
