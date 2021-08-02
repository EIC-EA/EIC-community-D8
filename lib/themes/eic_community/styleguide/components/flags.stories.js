import docs from './flags.docs.mdx';

import FlagsTemplate from '@theme/patterns/components/flags.html.twig';

import flags from '@theme/data/flags.data';

export const Base = () =>
  FlagsTemplate(flags);

export default {
  title: 'Components / Flags',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
