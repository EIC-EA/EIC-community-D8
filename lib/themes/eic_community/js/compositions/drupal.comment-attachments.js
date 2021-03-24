/**
 * @file
 * Implements the interaction logic for each commentAttachments composition.
 */
(function (ECL, Drupal) {
  Drupal.behaviors.commentAttachments = {
    attach: function () {
      const commentAttachments = document.querySelectorAll(
        '.ecl-comment__attachments:not(.ecl-comment__attachments--is-ready)'
      );

      if (!commentAttachments || !commentAttachments.length) {
        return;
      }

      for (let i = 0; i < commentAttachments.length; i++) {
        defineCommentAttachments(commentAttachments[i]);
      }
    },
  };

  /**
   * Makes the comment attachment collapsible.
   *
   * @param {HTMLElement} commentAttachments
   * @returns
   */
  function defineCommentAttachments(commentAttachments) {
    const commentAttachmentsItem = commentAttachments.querySelectorAll(
      '.ecl-comment__attachments-item'
    );

    const commentAttachmentCollapse = commentAttachments.querySelector(
      '.ecl-comment__attachments-collapse'
    );

    const commentAttachmentExpand = commentAttachments.querySelector(
      '.ecl-comment__attachments-expand'
    );

    const attachmentLength =
      parseInt(commentAttachments.getAttribute('data-comment-attachment-max-length')) || 10;

    if (
      commentAttachmentsItem.length < attachmentLength ||
      !commentAttachmentCollapse ||
      !commentAttachmentExpand
    ) {
      return;
    }

    collapse(commentAttachments);

    commentAttachmentCollapse.addEventListener('click', (event) => {
      event.preventDefault();

      collapse(commentAttachments);
    });

    commentAttachmentExpand.addEventListener('click', (event) => {
      event.preventDefault();

      expand(commentAttachments);
    });

    commentAttachments.classList.add('ecl-comment__attachments--is-ready');
  }

  /**
   * Collapses the selected commentAttachments element.
   *
   * @param {HTMLElement} commentAttachments
   */
  const collapse = (context) => {
    context && context.setAttribute('aria-collapsed', 'true');
  };

  /**
   * Expands the selected commentAttachments element.
   *
   * @param {HTMLElement} context
   */
  const expand = (context) => {
    context && context.removeAttribute('aria-collapsed');
  };
})(ECL, Drupal);
