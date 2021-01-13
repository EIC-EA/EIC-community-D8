export default {
  title: 'Compositions / Author',
};

import authorTemplate from '~/patterns/compositions/author.html.twig';
import author from '~/data/author.data.js';

export const Base = () =>
  authorTemplate({
    author: 'Jane Doe',
  });

export const Small = () =>
  authorTemplate({
    author: 'Jane Doe',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const Medium = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'medium',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const Large = () =>
  authorTemplate({
    author: 'Jane Doe',
    size: 'large',
    media: {
      image: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });
