import docs from './author.docs.mdx';

import authorTemplate from '@theme/patterns/components/author.html.twig';
import author from '@theme/data/author.data.js';

export const InitialsFallback = () =>
  authorTemplate({
    author: 'Jane Doe',
  });

export const AvatarSmall = () =>
  authorTemplate({
    author: 'Jane Doe',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarMedium = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'medium',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarLarge = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'large',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export default {
  title: 'Components / Author',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
