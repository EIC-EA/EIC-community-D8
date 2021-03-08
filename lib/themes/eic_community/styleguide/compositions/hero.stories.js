import docs from './hero.docs.mdx';

import HeroTemplate from '@theme/patterns/compositions/hero.html.twig';

import hero from '@theme/data/hero.data.js';

export const Base = () => HeroTemplate(hero);

export default {
  title: 'Compositions / Hero',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
