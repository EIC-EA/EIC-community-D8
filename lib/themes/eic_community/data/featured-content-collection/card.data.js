import { mockItems } from '@theme/snippets';

import cardWrapperTemplate from '@theme/patterns/compositions/card-wrapper.html.twig';

export default {
  title: 'Cupidatat duis nulla et Lorem nulla duis mollit proident.',
  description:
    'Commodo ex laboris do velit reprehenderit voluptate in dolore reprehenderit aute voluptate eiusmod anim.',
  items: mockItems(
    {
      content: cardWrapperTemplate({
        card: {
          title: {
            label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
          },
          description:
            'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
          image: {
            src: 'http://placehold.it/360x240',
            alt: 'Example card',
          },
        },
      }),
    },
    6
  ),
};
