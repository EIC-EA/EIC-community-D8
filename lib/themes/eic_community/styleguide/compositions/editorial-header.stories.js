import docs from './editorial-header.docs.mdx';

import editorialHeaderTemplate from '@theme/patterns/compositions/editorial-header.html.twig';
import editorialHeader from '@theme/data/editorial-header.data';

export const Base = () => editorialHeaderTemplate(editorialHeader);

export const WithActions = () =>
  editorialHeaderTemplate(
    Object.assign(
      {
        actions: [
          {
            label: 'Bookmark',
            icon: {
              type: 'custom',
              name: 'star_circle',
            },
          },
          {
            label: 'Share',
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
