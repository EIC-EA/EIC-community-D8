import docs from './author.docs.mdx';

import AuthorTemplate from '@theme/patterns/components/author.html.twig';

import author from '@theme/data/author.data.js';

export const InitialsFallback = () =>
  AuthorTemplate({
    name: 'Jane Doe',
  });

export const AvatarSmall = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    image: {
      src: 'https://picsum.photos/144/144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarMedium = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    size: 'medium',
    image: {
      src: 'https://picsum.photos/144/144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const AvatarLarge = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    size: 'large',
    image: {
      src: 'https://picsum.photos/144/144',
      alt: 'Avatar image of Jane Doe',
    },
  });

export const WithDescription = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    description: 'Duis id Lorem in esse nisi',
  });

export const Clickable = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    path: '#author',
  });

export const Mailto = () => AuthorTemplate(author);

export const WithUpdates = () =>
  AuthorTemplate({
    name: 'Jane Doe',
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
