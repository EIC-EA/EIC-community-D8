import docs from './content-type-indicator.docs.mdx';

import ContentTypeIndicatorTemplate from '@theme/patterns/components/content-type-indicator.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  ContentTypeIndicatorTemplate({
    label: 'News',
    icon_file_path: common.icon_file_path,
    icon: {
      name: 'news',
      type: 'custom',
    },
  });

export default {
  title: 'Components / Content Type Indicator',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
