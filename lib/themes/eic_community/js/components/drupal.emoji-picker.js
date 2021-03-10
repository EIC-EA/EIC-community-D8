/**
 * @file
 * Implements the interaction logic for each emojiPicker composition.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.emojiPicker = {
    attach: function () {
      const emojiPicker = document.querySelectorAll(
        '.ecl-emoji-picker:not(.ecl-emoji-picker--is-ready)'
      );

      if (!emojiPicker || !emojiPicker.length) {
        return;
      }

      for (let i = 0; i < emojiPicker.length; i++) {
        defineEmojiPicker(emojiPicker[i]);
      }
    },
  };

  function defineEmojiPicker(emojiPicker) {
    const emojiPickerTrigger = emojiPicker.querySelector('.ecl-emoji-picker__trigger');
    const emojiPickerElement = emojiPicker.querySelector('unicode-emoji-picker');
    const emojiPickerTarget = document.querySelector(
      emojiPicker.getAttribute('data-emoji-picker-target')
    );

    if (!emojiPickerTrigger || !emojiPickerElement || !emojiPickerTarget) {
      return;
    }

    document.addEventListener('keydown', (event) => {
      // Only accept the escape key.
      if (!event.keyCode || event.keyCode !== 27) {
        return;
      }

      collapse(emojiPicker);
    });

    document.addEventListener('click', (event) => {
      if (event.target === emojiPicker || emojiPicker.contains(event.target)) {
        return;
      }

      collapse(emojiPicker);
    });

    emojiPickerTrigger.addEventListener('click', (event) => {
      event.preventDefault();

      toggle(emojiPicker);
    });

    emojiPickerElement.addEventListener('emoji-pick', (event) => {
      if (event.detail && event.detail.emoji) {
        emojiPickerTarget.value = emojiPickerTarget.value
          ? `${emojiPickerTarget.value} ${event.detail.emoji}`
          : event.detail.emoji;
      }
    });

    emojiPicker.classList.add('ecl-emoji-picker--is-ready');
  }

  const toggle = (emojiPicker) => {
    if (!emojiPicker) {
      return;
    }

    if (emojiPicker.getAttribute('aria-expanded')) {
      collapse(emojiPicker);
    } else {
      expand(emojiPicker);
    }
  };

  const collapse = (emojiPicker) => {
    emojiPicker && emojiPicker.removeAttribute('aria-expanded');
  };

  const expand = (emojiPicker) => {
    emojiPicker && emojiPicker.setAttribute('aria-expanded', true);
  };
})(ECL, Drupal);
