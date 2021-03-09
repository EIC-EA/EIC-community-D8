import docs from './flag.docs.mdx';

import FlagTemplate from '@theme/patterns/components/flag.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  FlagTemplate({
    label: 'Netherlands',
  });

export const Image = () =>
  FlagTemplate({
    label: 'Netherlands',
    code: 'NL',
  });

export const CustomImage = () =>
  FlagTemplate({
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
