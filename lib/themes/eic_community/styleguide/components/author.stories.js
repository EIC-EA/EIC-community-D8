export default {
  title: 'Components / Author',
};

import authorTemplate from '~/patterns/components/author.html.twig';
import author from '~/data/author.data.js';

export const InitialsFallback = () =>
  authorTemplate({
    author: 'Jane Doe',
  });

export const AvatarSmall = () =>
  authorTemplate({
    author: 'Jane Doe',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarMedium = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'medium',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarLarge = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'large',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });
