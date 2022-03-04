import docs from './toggle-switch.docs.mdx';

import ToggleTemplate from '@theme/patterns/components/toggle-switch.html.twig';

export const Base = () =>
  ToggleTemplate({
    name: 'string',
    state: true
  });

export default {
  title: 'Components / Toggle switch',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
