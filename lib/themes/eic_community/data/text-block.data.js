import { editableField } from '~/snippets';

export default {
  title: 'Sit elit sunt reprehenderit labore exercitation.',
  items: [
    { content: editableField('Cillum culpa occaecat voluptate nulla non.') },
    {
      content: editableField(
        'Ut pariatur qui eiusmod cupidatat sit laboris nulla ex enim sunt veniam.'
      ),
    },
    {
      content: editableField(
        'Consequat nisi aute dolore velit duis do magna excepteur pariatur aliquip aliqua deserunt ullamco.'
      ),
      call_to_action: {
        link: {
          type: 'standalone',
          label: 'Standalone link',
          path: 'http://google.com',
          icon_position: 'after',
          aria_label: 'An aria label',
        },
      },
    },
  ],
  media: {
    image: 'http://placehold.it/320x240',
    alt: 'Alternate text',
  },
};
