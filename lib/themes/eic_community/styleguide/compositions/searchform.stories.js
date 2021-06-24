import SearchformTemplate from '@theme/patterns/compositions/search/searchform.html.twig';

import common from '@theme/data/common.data';

import { editableField } from '@theme/snippets';

export const Base = () =>
  SearchformTemplate({
    icon_file_path: common.icon_file_path,
  });

export const WithTooltip = () =>
  SearchformTemplate({
    icon_file_path: common.icon_file_path,
    tooltip: {
      label: 'Advanced Search',
      content: editableField(),
    },
  });

export default {
  title: 'Compositions / Searchform',
};
