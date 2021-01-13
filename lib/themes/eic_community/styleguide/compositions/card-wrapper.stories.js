export default {
  title: 'Compositions / Card Wrapper',
};

import cardWrapperTemplate from '~/patterns/compositions/card-wrapper.html.twig';

export const Base = () =>
  cardWrapperTemplate({
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
  });

export const WithAuthor = () =>
  cardWrapperTemplate({
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
    authors: [
      {
        author: 'John doe',
        media: {
          image: 'http://placehold.it/140x140',
          alt: 'Avatar image of John doe',
        },
      },
    ],
  });
