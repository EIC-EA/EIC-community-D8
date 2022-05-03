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
    size: 'medium'
  });

export const Clickable = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    path: '#author',
    size: 'medium'
  });

export const Mailto = () => AuthorTemplate(Object.assign({size: 'medium'}, author));

export const WithUpdates = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    updates: {
      label: 'Updates',
      value: 8,
    },
  });

export const WithMeta = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    meta: 'Duis id Lorem in esse nisi',
  });

export const AvatarOnly = () =>
  AuthorTemplate({
    name: 'Jane Doe',
    display_avatar_only: true,
    image: {
      src: 'https://picsum.photos/144/144',
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
