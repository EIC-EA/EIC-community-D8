/**
 * @file
 * Implements the interaction logic for the collapsible options.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.mediaWrapper = {
    attach: function () {
      const mediaWrapper = document.querySelectorAll(
        '.ecl-media-wrapper:not(.ecl-media-wrapper--is-ready)'
      );

      if (!mediaWrapper || !mediaWrapper.length) {
        return;
      }

      for (let i = 0; i < mediaWrapper.length; i++) {
        defineMediaWrapper(mediaWrapper[i]);
      }
    },
  };

  /**
   * Attaches the logic for the current MediaWrapper HTMLElement.
   */
  function defineMediaWrapper(mediaWrapper) {
    const video = mediaWrapper.querySelector('video');

    if (!video) {
      return;
    }

    mediaWrapper.classList.add('ecl-media-wrapper--has-media');

    video.addEventListener('play', () => {
      mediaWrapper.classList.add('ecl-media-wrapper--is-playing');
      mediaWrapper.classList.remove('ecl-media-wrapper--is-paused');
    });

    video.addEventListener('pause', () => {
      mediaWrapper.classList.remove('ecl-media-wrapper--is-playing');
      mediaWrapper.classList.add('ecl-media-wrapper--is-paused');
    });

    video.addEventListener('ended', () => {
      mediaWrapper.classList.remove('ecl-media-wrapper--is-paused');
      mediaWrapper.classList.remove('ecl-media-wrapper--is-playing');
    });

    video.addEventListener('loadeddata', () => {
      mediaWrapper.classList.remove('ecl-media-wrapper--is-paused');
      mediaWrapper.classList.remove('ecl-media-wrapper--is-ready');
    });
  }
})(ECL, Drupal);
