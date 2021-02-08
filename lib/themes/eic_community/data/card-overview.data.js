import { mockItems } from '@theme/snippets';
import common from './common.data';

export default {
  title: 'Cupidatat duis nulla et Lorem nulla duis mollit proident.',
  description:
    'Commodo ex laboris do velit reprehenderit voluptate in dolore reprehenderit aute voluptate eiusmod anim.',
  icon_file_path: common.icon_file_path,
  items: mockItems(
    {
      card: {
        title: {
          label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
        },
        path: '?path=card',
        description:
          'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
        image: {
          src: 'https://picsum.photos/320/240',
          alt: 'Example card',
        },
      },
    },
    6
  ),
};
