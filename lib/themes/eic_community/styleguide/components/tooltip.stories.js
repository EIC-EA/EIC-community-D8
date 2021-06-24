import docs from './timestamp.docs.mdx';

import TooltipTemplate from '@theme/patterns/components/tooltip.html.twig';

import common from '@theme/data/common.data';

import { editableField } from '@theme/snippets';

export const Base = () =>
  TooltipTemplate({
    label: 'Information about search',
    content: editableField(),
    icon_file_path: common.icon_file_path,
  });

export const Invert = () =>
  TooltipTemplate({
    label: 'Information about search',
    content: editableField(),
    icon_file_path: common.icon_file_path,
    invert: true,
  });

export const Reversed = () =>
  TooltipTemplate({
    label: 'Information about search',
    extra_classes: 'ecl-tooltip--aligns-from-right',
    content: editableField(),
    icon_file_path: common.icon_file_path,
  });

export default {
  title: 'Components / Tooltip',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
