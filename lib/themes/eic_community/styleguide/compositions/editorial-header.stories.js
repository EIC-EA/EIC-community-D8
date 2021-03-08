import docs from './editorial-header.docs.mdx';

import EditorialHeaderTemplate from '@theme/patterns/compositions/editorial-header.html.twig';

import editorialHeader from '@theme/data/editorial-header.data';

export const Base = () => EditorialHeaderTemplate(editorialHeader);

export const WithActions = () =>
  EditorialHeaderTemplate(
    Object.assign(
      {
        actions: [
          {
            link: {
              label: 'Bookmark',
            },
            icon: {
              type: 'custom',
              name: 'star_circle',
            },
          },
          {
            link: {
              label: 'Share',
            },
            icon: {
              type: 'general',
              name: 'share',
            },
          },
        ],
      },
      editorialHeader
    )
  );

export default {
  title: 'Compositions / Editorial Header',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
