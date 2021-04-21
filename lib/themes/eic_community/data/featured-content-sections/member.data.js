import { mockItems, without } from '@theme/snippets';

import TeaserOverviewTemplate from '@theme/patterns/compositions/teaser-overview.html.twig';
import MemberTeaserTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';

import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default (title) => ({
  title: title,
  content: TeaserOverviewTemplate({
    title: title,
    icon_file_path: common.icon_file_path,
    extra_classes: 'ecl-teaser-overview--has-compact-layout ecl-teaser-overview--has-columns',
    call_to_action: {
      link: {
        label: 'Show 8 more',
      },
    },
    items: mockItems(
      {
        content: MemberTeaserTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-teaser--as-grey',
              timestamp: {
                label: 'Last active: 3 minutes ago.',
              },
            },
            without(teaser.member, 'actions')
          )
        ),
      },
      6
    ),
  }),
});
