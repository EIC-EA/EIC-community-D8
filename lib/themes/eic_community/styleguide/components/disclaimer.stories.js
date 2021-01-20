import docs from './disclaimer.docs.mdx';

import blockquoteTemplate from '@theme/patterns/components/blockquote.html.twig';

import editableField from '@themes/snippets';

export const Base = () =>
  blockquoteTemplate({
    content: editableField(),
  });

export default {
  title: 'Components / Blockquote',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
