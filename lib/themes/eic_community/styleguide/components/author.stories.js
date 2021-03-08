import docs from './author.docs.mdx';

import AuthorTemplate from '@theme/patterns/components/author.html.twig';

import author from '@theme/data/author.data.js';

export const InitialsFallback = () =>
  AuthorTemplate({
    author: 'Jane Doe',
  });

export const AvatarSmall = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarMedium = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    size: 'medium',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarLarge = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    size: 'large',
    image: {
      src: 'http://placehold.it/144x144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const WithDescription = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    description: 'Duis id Lorem in esse nisi',
  });

export const Clickable = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    path: '#author',
  });

export const Mailto = () => AuthorTemplate(author);

export const WithUpdates = () =>
  AuthorTemplate({
    author: 'Jane Doe',
    updates: {
      label: 'Updates',
      value: 8,
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
