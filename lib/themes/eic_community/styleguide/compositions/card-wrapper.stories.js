import docs from './card-wrapper.docs.mdx';

import CardWrapperTemplate from '@theme/patterns/compositions/card-wrapper.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  CardWrapperTemplate({
    card: {
      title: {
        label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
      },
      description:
        'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
      image: {
        src: 'https://picsum.photos/360/240',
        alt: 'Example card',
      },
      path: '?path=card-wrapper-basic',
    },
  });

export const WithMetaInformation = () =>
  CardWrapperTemplate({
    card: {
      title: {
        label: 'Eiusmod amet excepteur ea consectetur ut esse tempor ea excepteur nostrud.',
      },
      description:
        'Aliquip labore elit qui dolore exercitation ea veniam cillum adipisicing nostrud eu id.',
      image: {
        src: 'https://picsum.photos/360/240',
        alt: 'Example card',
      },
      meta: ['Meta 1', 'Meta 2', 'Meta 3'],
      infos: [
        {
          label: '2018/10/22',
          icon: {
            type: 'general',
            name: 'calendar',
          },
        },
        {
          label: 'Luxembourg',
          icon: {
            type: 'general',
            name: 'location',
          },
        },
      ],
      tags: [
        {
          label: 'Tag 1',
          path: '/example-1',
        },
        {
          label: 'Tag 2',
          path: '/example-2',
        },
        {
          label: 'Tag 3',
          path: '/example-3',
        },
      ],
      authors: [
        {
          author: 'John doe',
          image: {
            src: 'https://picsum.photos/140/140',
            alt: 'Avatar image of John doe',
          },
        },
      ],
    },
    icon_file_path: common.icon_file_path,
  });

export default {
  title: 'Compositions / Card Wrapper',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
