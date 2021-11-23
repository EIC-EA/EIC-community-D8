import { mockItems } from '@theme/snippets';

import TeaserTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';


import common from '@theme/data/common.data';
import teaser from '@theme/data/teaser';
import organisation from "../teaser/organisation.data";

export default {
  title: 'Related companies',
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
            extra_classes: 'ecl-teaser--grey',
          },
          teaser.organisation
        )
      ),
    },
    4
  ),

};
