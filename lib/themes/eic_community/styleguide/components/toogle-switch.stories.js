import docs from './toogle-switch.docs.mdx';

import ToogleTemplate from '@theme/patterns/components/toogle-switch.html.twig';

export const Base = () =>
  ToogleTemplate({
    name: 'string',
    state: true
  });

export default {
  title: 'Components / Toogle switch',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
