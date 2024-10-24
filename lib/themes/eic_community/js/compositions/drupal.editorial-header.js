/**
 * @file
 * Implements the logic for the editorial header HTMLElements.
 */
(function (Drupal) {
  Drupal.behaviors.editorialHeader = {
    attach: function (context) {
      // Use native JavaScript to select the read-more link and other related elements from the context.
      const readMoreLink = context.querySelector('.js-readmore');
      const parentParagraph = context.querySelector('.ecl-editorial-header__description');

      // Ensure all elements are available before adding event listener
      if (!readMoreLink || !parentParagraph) {
        return; // Exit if no readMoreLink or parentParagraph found
      }

      const truncatedText = parentParagraph.querySelector('.truncated-text');
      const ellipsis = parentParagraph.querySelector('.ellipsis');

      // Single check for all required elements.
      if (!truncatedText || !ellipsis) {
        return; // Exit if any required element is missing
      }

      // Add click event listener to the read-more link.
      readMoreLink.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent default anchor behavior

        // Toggle the "state-preview" class on the parent paragraph.
        const isPreviewState = parentParagraph.classList.toggle('state-preview');

        // Translate the "Read more" and "Close" text.
        const readMoreText = Drupal.t('Read more');
        const closeText = Drupal.t('Close');

        // Toggle classes and text based on state.
        truncatedText.classList.toggle('ecl-u-d-none', isPreviewState);
        truncatedText.classList.toggle('ecl-u-d-inline', !isPreviewState);
        ellipsis.classList.toggle('ecl-u-d-none', !isPreviewState);
        readMoreLink.classList.toggle('ecl-u-d-block', !isPreviewState);
        readMoreLink.textContent = isPreviewState ? readMoreText : closeText;
      });
    },
  };
})(Drupal);
