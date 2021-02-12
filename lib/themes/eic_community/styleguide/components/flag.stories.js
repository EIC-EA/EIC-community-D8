import docs from './flag.docs.mdx';

import flagTemplate from '@theme/patterns/components/flag.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  flagTemplate({
    label: 'Netherlands',
  });

export const Image = () =>
  flagTemplate({
    label: 'Netherlands',
    code: 'NL',
  });

export const CustomImage = () =>
  flagTemplate({
    label: 'Netherlands',
    image: {
      src: 'https://picsum.photos/48/48',
    },
  });

export default {
  title: 'Components / Flag',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
