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

    if (featuredContentSections.classList.contains('ecl-featured-content-sections--as-tabs')) {
      defineFeaturedContentSectionsAsTabs(featuredContentSections, items, links);
    } else {
      defineFeaturedContentSectionsAsList(featuredContentSections, items, links);
    }

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
    const { scrollY, innerHeight } = window;
    const visible = [];
    let direction = '';

    if (!items.length) {
      return;
    }

    for (let i = 0; i < items.length; i++) {
      items[i].removeAttribute('aria-current', true);

      if (isVisible(items[i])) {
        visible.push(items[i]);
      }
    }

    if (!visible.length) {
      return;
    }

    let visibleNode = visible[0];

    // Downwards scroll.
    if (scrollY > featuredContentSections.lastKnownScrollPosition) {
      direction = 'down';

      // Force the last item to be active after the scrollTop is larger than
      // it's offset position.
      if (visible.length >= 2 && items[items.length - 1] === visible[visible.length - 1]) {
        const { top, height } = visible[visible.length - 1].getBoundingClientRect();

        if (scrollY + innerHeight >= scrollY + top + height) {
          visibleNode = items[items.length - 1];
        }
      }
    }

    // Should implement Upwards scroll condition:
    //
    // if (scrollY < featuredContentSections.lastKnownScrollPosition) {
    //   ...
    // }

    // Also Mark the actual section.
    if (visibleNode) {
      visibleNode.setAttribute('aria-current', true);

      // Mark the section link that should link to the visible section.
      if (links.length) {
        for (let i = 0; i < links.length; i++) {
          const href = links[i].getAttribute('href');

          links[i].removeAttribute('aria-current');

          if (featuredContentSections.querySelector(href) === visibleNode) {
            links[i].setAttribute('aria-current', true);
          }
        }
      }
    }

    // Cache the current scroll position to determine the direction for the next
    // scroll event.
    featuredContentSections.lastKnownScrollPosition = scrollY;
  }

  /**
   * Implements the navigation list as one page anchor scroll navigation.
   *
   * @param {HTMLElement} featuredContentSections Binds the logic from the
   * defined featuredContentSections element.
   * @param {NodeList} items Should contain the item containers for the defined
   * featuredContentSections element.
   * @param {NodeList} links Contains the anchor links that should refer to the
   * defined items argument.
   */
  function defineFeaturedContentSectionsAsList(featuredContentSections, items, links) {
    if (links[0].offsetParent) {
      defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links);
    }

    document.addEventListener('scroll', () => {
      featuredContentSections.throttle && clearTimeout(featuredContentSections.throttle);

      if (links[0].offsetParent) {
        featuredContentSections.throttle = setTimeout(() => {
          defineFeaturedContentSectionsActiveItem(featuredContentSections, items, links);
        }, 20);
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
  }

  /**
   * Implements the navigation list as tabbed navigation.
   *
   * @param {HTMLElement} featuredContentSections Binds the logic from the
   * defined featuredContentSections element.
   * @param {NodeList} items Should contain the item containers for the defined
   * featuredContentSections element.
   * @param {NodeList} links Contains the anchor links that should refer to the
   * defined items argument.
   */
  function defineFeaturedContentSectionsAsTabs(featuredContentSections, items, links) {
    if (!links) {
      return;
    }

    let initialTab =
      featuredContentSections.getAttribute('data-featured-content-sections-initial-tab') || 0;

    const { hash } = window.location;
    const hashCollection = [];

    for (let i = 0; i < links.length; i++) {
      links[i].addEventListener('click', (event) => {
        event.preventDefault();

        selectSection(items, links, i);
      });

      // Ensure the initial tab is not used if there is already a hash defined.
      if (hash === links[i].hash) {
        initialTab = i;
      }
    }

    // Also change the tabs during the browser navigation.
    window.addEventListener('popstate', (event) => {
      const { location } = window;
      if (!location) {
        return;
      }

      const { hash } = location;
      const target = document.querySelector(hash);

      if (!target || !target.parentElement) {
        return;
      }
    });

    selectSection(items, links, initialTab, true);
  }

  /**
   * Marks the selected section & link element with the [aria-current] attribute.
   *
   * @param {NodeList} items Should contain the item containers for the defined
   * featuredContentSections element.
   * @param {NodeList} links Contains the anchor links that should refer to the
   * defined items argument.
   * @param {number} index The selected index that be marked as current.
   * @param {boolean} preventUpdate Prevents the update of the location hash.
   */
  const selectSection = (items, links, index, preventUpdate) => {
    for (let i = 0; i < items.length; i++) {
      if (i === index) {
        items[i].setAttribute('aria-current', 'true');
        items[i].removeAttribute('aria-hidden');
        items[i].removeAttribute('focusable');
      } else {
        items[i].removeAttribute('aria-current');
        items[i].setAttribute('aria-hidden', 'true');
        items[i].setAttribute('focusable', 'false');
      }
    }

    let hash;
    for (let i = 0; i < links.length; i++) {
      if (i === index) {
        links[i].setAttribute('aria-current', 'true');
        hash = links[i].hash;
      } else {
        links[i].removeAttribute('aria-current');
      }
    }

    const id = hash.indexOf('#') >= 0 ? hash : false;

    if (!preventUpdate && id && document.querySelector(id)) {
      if (window.history.pushState) {
        window.history.pushState(null, null, id);
      } else {
        window.location.hash = id.replace('#', '');
      }
    }
  };

  const getIndexFromCurrentLocation = () => {
    return 0;
  };

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
