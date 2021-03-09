import docs from './pagination.docs.mdx';

import PaginationTemplate from '@theme/patterns/compositions/pagination.html.twig';

import pagination from '@theme/data/pagination.data';

export const Base = () => PaginationTemplate(pagination);

export default {
  title: 'Compositions / Pagination',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
