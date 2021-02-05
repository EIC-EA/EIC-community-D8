import { mockItems } from '@theme/snippets';

console.log(
  mockItems(
    {
      card: {
        title: {
          label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
        },
        description:
          'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
        image: {
          src: 'https://picsum.photos/320x240',
          alt: 'Example card',
        },
      },
    },
    6
  )
);

export default {
  title: 'Cupidatat duis nulla et Lorem nulla duis mollit proident.',
  description:
    'Commodo ex laboris do velit reprehenderit voluptate in dolore reprehenderit aute voluptate eiusmod anim.',
  items: mockItems(
    {
      card: {
        title: {
          label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
        },
        description:
          'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
        image: {
          src: 'https://picsum.photos/320x240',
          alt: 'Example card',
        },
      },
    },
    6
  ),
};
