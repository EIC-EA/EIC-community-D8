import docs from './disclaimer.docs.mdx';

import blockquoteTemplate from '@theme/patterns/components/disclaimer.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  blockquoteTemplate({
    content: editableField(),
  });

export default {
  title: 'Components / Disclaimer',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
