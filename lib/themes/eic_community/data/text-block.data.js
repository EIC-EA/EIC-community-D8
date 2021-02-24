import { editableDefinitions, editableField, editableList } from '@theme/snippets';

export default {
  title: 'Sit elit sunt reprehenderit labore exercitation.',
  items: [
    { content: editableField('Cillum culpa occaecat voluptate nulla non.') },
    {
      content: editableList(3, 'ul'),
    },
    {
      content: editableField(
        'Consequat nisi aute dolore velit duis do magna excepteur pariatur aliquip aliqua deserunt ullamco.'
      ),
      call_to_action: {
        link: {
          type: 'standalone',
          extra_classes: '',
          label: 'Standalone link',
          path: 'http://google.com',
          icon_position: 'after',
          aria_label: 'An aria label',
        },
      },
    },
  ],
  media: {
    image: 'https://picsum.photos/544/312',
    alt: 'Alternate text',
  },
};
