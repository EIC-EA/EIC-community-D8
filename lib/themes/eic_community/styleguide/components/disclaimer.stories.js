import docs from './disclaimer.docs.mdx';

import BlockquoteTemplate from '@theme/patterns/components/disclaimer.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  BlockquoteTemplate({
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
