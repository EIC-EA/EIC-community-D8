import { mockItems } from '@theme/snippets';

import ExtendedListTemplate from '@theme/patterns/compositions/extended-list/extended-list.html.twig';

import extendedList from '@theme/data/extended-list.data';

export default (title) => ({
  title: title,
  content: ExtendedListTemplate(extendedList),
});
