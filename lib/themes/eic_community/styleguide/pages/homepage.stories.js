import homepagePublicTemplate from '@theme/patterns/compositions/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';

export const homepagePublic = () =>
  homepagePublicTemplate({
    common: common,
    hero: {
      insertion: 'Welcome to the EIC Community',
      title: 'Your community to find partners and share knowledge.',
      description: 'Ipsum in culpa sunt commodo quis quis sunt reprehenderit ipsum dolore minim.',
      image: 'https://picsum.photos/1600/450',
      actions: [
        {
          link: {
            label: 'Register',
            path: '?path=register',
            type: 'cta',
          },
        },
        {
          link: {
            label: 'Login',
            path: '?path=login',
            type: 'standalone',
          },
        },
      ],
    },
  });

export default {
  title: 'Pages / Homepage',
};
