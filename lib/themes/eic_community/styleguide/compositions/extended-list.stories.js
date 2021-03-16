import docs from './extended-list.docs.mdx';

import ExtendedListTemplate from '@theme/patterns/compositions/extended-list/extended-list.html.twig';

import extendedList from '@theme/data/extended-list.data';

import { mockItems } from '@theme/snippets';

export const Base = () => ExtendedListTemplate(extendedList);

export default {
  title: 'Compositions / Extended List',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
