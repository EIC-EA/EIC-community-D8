import docs from './timestamp.docs.mdx';

import timestampTemplate from '@theme/patterns/components/timestamp.html.twig';
import common from '../../data/common.data';

export const Base = () =>
  timestampTemplate({
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
