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

    if (
      commentAttachmentsItem.length < 3 ||
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

  const collapse = (commentAttachments) => {
    commentAttachments && commentAttachments.setAttribute('aria-collapsed', 'true');
  };

  const expand = (commentAttachments) => {
    commentAttachments && commentAttachments.removeAttribute('aria-collapsed');
  };
})(ECL, Drupal);
