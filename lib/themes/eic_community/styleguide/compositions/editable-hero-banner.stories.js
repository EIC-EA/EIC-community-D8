import docs from './editable-hero-banner.docs.mdx';

import editableHeroBanner from '@theme/patterns/compositions/editable-hero-banner.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  editableHeroBanner({
    content: editableField(),
    image: 'https://picsum.photos/1600/450',
  });

export default {
  title: 'Compositions / Editable Hero Banner',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
