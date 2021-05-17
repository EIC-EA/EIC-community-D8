import { lorem } from 'faker';

import HarmonicaTemplate from '@theme/patterns/components/harmonica.html.twig';
import WellTemplate from '@theme/patterns/components/well.html.twig';

import common from '@theme/data/common.data';
import { editableField, editableList } from '@theme/snippets';

export default {
  title: 'Group Permissions',
  description: lorem.paragraph(),
  items: [
    {
      title: 'Group Access',
      content: WellTemplate({
        content: HarmonicaTemplate({
          title: 'Custom restriction',
          icon: {
            icon: {
              name: 'lock',
              type: 'custom',
            },
          },
          icon_file_path: common.icon_file_path,
          items: [
            {
              title: 'Certain organisation types',
              content: editableList(3, 'ul'),
            },
            {
              title: 'Specific organisations',
              content: editableList(2, 'ul'),
            },
            {
              title: 'Specific trusted users',
              content: editableList(9, 'ul'),
            },
          ],
        }),
      }),
    },
  ],
};
