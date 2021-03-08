import docs from './extended-list.docs.mdx';

import extendedListTemplate from '@theme/patterns/compositions/extended-list/extended-list.html.twig';

import { mockItems } from '@theme/snippets';

export const Base = () =>
  extendedListTemplate({
    title: 'Duis minim duis enim officia exercitation dolor dolore ullamco ut.',
    items: mockItems(
      {
        title: 'Ipsum dolore dolor duis sint.',
        items: mockItems(
          {
            title: 'Lorem labore adipisicing.',
            items: mockItems(
              {
                title: 'Magna laborum cillum cupidatat non ad.',
                items: mockItems(
                  {
                    title: 'Consequat laborum fugiat ad culpa quis.',
                  },
                  4
                ),
              },
              3
            ),
          },
          2
        ),
      },
      3
    ),
  });

export default {
  title: 'Compositions / Extended List',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
