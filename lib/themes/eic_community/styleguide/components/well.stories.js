import docs from './well.docs.mdx';

import WellTemplate from '@theme/patterns/components/well.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  WellTemplate({
    content: editableField(),
  });

export const Grey = () =>
  WellTemplate({
    extra_classes: 'ecl-well--is-grey',
    content: editableField(),
  });

export default {
  title: 'Components / Well',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
