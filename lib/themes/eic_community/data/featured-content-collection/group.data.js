import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Most active groups',
  description:
    'Sit aliquip commodo ut laboris adipisicing laborum reprehenderit veniam elit ad deserunt.',
  call_to_action: {
    link: {
      label: 'Show more',
    },
  },
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      content: TeaserTemplate(
        Object.assign(
          {
            extra_classes: 'ecl-teaser--as-card ecl-teaser--as-card-grey',
          },
          teaser.group
        )
      ),
    },
    3
  ),
};
