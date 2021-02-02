import homepagePublicTemplate from '@theme/patterns/compositions/homepage/homepage.public.html.twig';

import common from '@theme/data/common.data';

export const homepagePublic = () =>
  homepagePublicTemplate({
    common: common,
    hero_image: {
      src: 'https://picsum.photos/1200/400',
    },
  });

export default {
  title: 'Pages / Homepage',
};
