/**
 * @file
 * Implements the logic for the editorial article HTMLElements.
 */
(function (Drupal) {
  Drupal.behaviors.editorialArticle = {
    attach: function () {
      const editorialArticle = document.querySelectorAll(
        '.ecl-editorial-article:not(.ecl-editorial-article--is-ready)'
      );

      if (!editorialArticle.length) {
        return;
      }

      for (let i = 0; i < editorialArticle.length; i++) {
        defineEditorialArticle(editorialArticle[i]);
      }
    },
  };

  /**
   * Implements the collapse logic for the current editorial article element.
   *
   * @param {HTMLElement} editorialArticle
   */
  function defineEditorialArticle(editorialArticle) {
    if (!editorialArticle.classList.contains('ecl-editorial-article--is-collapsible')) {
      return;
    }

    window.addEventListener('resize', () => {
      editorialArticle.throttle && clearTimeout(editorialArticle.throttle);

      editorialArticle.throttle = setTimeout(
        () => defineEditorialArticleMinHeight(editorialArticle),
        200
      );
    });

    defineEditorialArticleMinHeight(editorialArticle);

    editorialArticle.classList.add('ecl-editorial-article--is-ready');

    // recalculate min height when article is expanded or collapsed
    editorialArticle.addEventListener('toggle', () => {
      defineEditorialArticleMinHeight(editorialArticle);
    });
  }

  function defineEditorialArticleMinHeight(editorialArticle) {
    if (!editorialArticle) {
      return;
    }

    const editorialArticleAside = editorialArticle.querySelector('.ecl-editorial-article__aside');

    if (!editorialArticleAside) {
      return;
    }

    if (editorialArticle.style) {
      editorialArticle.style.minHeight = `${editorialArticleAside.firstElementChild.offsetHeight}px`;
    }
  }
})(Drupal);
