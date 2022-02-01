import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/organisation/organisation.member.teaser.html.twig';


import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';

export default {
  title: 'Our team',
  call_to_action: {
    link: {
      label: 'See all team members',
    },
  },
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      content: TeaserTemplate(
        Object.assign(
          {
            extra_classes: 'ecl-teaser--grey',
          },
          teaser.organisation_member
        )
      ),
    },
    4
  ),

};
