import docs from './update-indicator.docs.mdx';

import UpdateIndcatorTemplate from '@theme/patterns/components/update-indicator.html.twig';

export const Base = () =>
  UpdateIndcatorTemplate({
    label: 'Updates',
    value: 13,
  });

export default {
  title: 'Components / Update Indicator',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
