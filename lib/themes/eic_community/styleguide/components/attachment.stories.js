import docs from './attachment.docs.mdx';

import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';

import attachment from '@theme/data/attachment.data';
import common from '@theme/data/common.data';

export const Base = () => AttachmentTemplate(attachment);

export const Compact = () =>
  AttachmentTemplate(
    Object.assign(
      {
        is_clickable: true,
      },
      attachment
    )
  );

  export const Highlighted = () =>
  AttachmentTemplate(
    Object.assign(
      {
        highlight: true
      },
      attachment
    )
  );

export default {
  title: 'Components / Attachment',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
