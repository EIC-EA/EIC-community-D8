import { lorem } from 'faker';

import HarmonicaTemplate from '@theme/patterns/components/harmonica.html.twig';
import { editableField } from '@theme/snippets';

import common from '@theme/data/common.data';

export const Base = () =>
  HarmonicaTemplate({
    title: lorem.text(),
    description: lorem.paragraph(),
    extra_attributes: [
      {
        name: 'data-harmonica-initial-index',
        value: 1,
      },
    ],
    icon_file_path: common.icon_file_path,
    items: [
      {
        title: 'Harmonica item #1',
        content: editableField(),
      },
      {
        title: 'Harmonica item #2',
        content: editableField(),
      },
      {
        title: 'Harmonica item #3',
        content: editableField(),
      },
    ],
  });

export default {
  title: 'Components / Harmonica',
};
