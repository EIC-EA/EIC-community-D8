import docs from './timestamp.docs.mdx';

import TimestampTemplate from '@theme/patterns/components/timestamp.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  TimestampTemplate({
    label: '8 minutes avarage read time',
    icon_file_path: common.icon_file_path,
  });

export default {
  title: 'Components / Timestamp',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
