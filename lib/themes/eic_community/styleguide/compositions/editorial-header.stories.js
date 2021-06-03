import docs from './editorial-header.docs.mdx';

import EditorialHeaderTemplate from '@theme/patterns/compositions/editorial-header.html.twig';

import editorialHeader from '@theme/data/editorial-header.data';

export const Base = () => EditorialHeaderTemplate(editorialHeader);

export const WithImage = () =>
  EditorialHeaderTemplate(
    Object.assign(
      {
        image: {
          src: 'http://picsum.photos/1200/400',
        },
      },
      editorialHeader
    )
  );

export const WithFlags = () =>
  EditorialHeaderTemplate(
    Object.assign(
      {
        flags: [
          {
            link: {
              label: 'Invite user',
            },
            icon: {
              name: 'invite',
              type: 'custom',
            },
          },
        ],
      },
      editorialHeader
    )
  );

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

export const WithStats = () =>
  EditorialHeaderTemplate(
    Object.assign(
      {
        stats: [
          {
            label: 'Members',
            path: '#foo',
            value: 294,
            icon: {
              name: 'group',
              type: 'custom',
            },
            updates: {
              label: 'Latest members from the past 14 days.',
              value: 14,
            },
          },
          {
            label: 'Comments',
            path: '#foo',
            value: 33,
            icon: {
              name: 'comment',
              type: 'custom',
            },
          },
          {
            label: 'Attachments',
            value: 4,
            icon: {
              name: 'documents',
              type: 'custom',
            },
          },
          {
            label: 'events',
            value: 2,
            icon: {
              name: 'calendar',
              type: 'custom',
            },
          },
        ],
      },
      editorialHeader
    )
  );

export const WithParentLink = () =>
  EditorialHeaderTemplate(
    Object.assign(
      {
        parent: {
          link: {
            label: 'Go back',
            path: '#path=parent-link',
          },
        },
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
