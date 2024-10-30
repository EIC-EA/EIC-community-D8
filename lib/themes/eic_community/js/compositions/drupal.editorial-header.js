/**
 * @file
 * Implements the logic for the editorial header HTMLElements.
 */
(function (Drupal) {
  Drupal.behaviors.editorialHeader = {
    attach: function (context) {
      // Use context to target specific areas on initial load or AJAX loads
      const descriptionContainer = context.querySelector('.ecl-editorial-header__description .preview-text');

      if (!descriptionContainer) return;

      const readMoreLink = descriptionContainer.querySelector('.js-readmore');
      const truncatedText = descriptionContainer.querySelector('.truncated-text');
      const ellipsis = descriptionContainer.querySelector('.ellipsis');

      if (!readMoreLink || !truncatedText || !ellipsis) return;

      // Prevent duplicate event attachment
      if (readMoreLink.classList.contains('initialized')) return;
      readMoreLink.classList.add('initialized');

      const readMoreText = Drupal.t('Read more');
      const closeText = Drupal.t('Close');

      readMoreLink.addEventListener('click', function (event) {
        event.preventDefault();
        const isPreviewState = descriptionContainer.classList.toggle('state-preview');
        truncatedText.classList.toggle('ecl-u-d-none', isPreviewState);
        truncatedText.classList.toggle('ecl-u-d-inline', !isPreviewState);
        ellipsis.classList.toggle('ecl-u-d-none', !isPreviewState);
        readMoreLink.textContent = isPreviewState ? readMoreText : closeText;
      });
    },
  };
})(Drupal);
