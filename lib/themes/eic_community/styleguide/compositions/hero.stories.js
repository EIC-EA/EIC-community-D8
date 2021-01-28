import docs from './hero.docs.mdx';

import heroTemplate from '@theme/patterns/compositions/hero.html.twig';
import hero from '@theme/data/hero.data.js';

export const Base = () => heroTemplate(hero);

export default {
  title: 'Compositions / Hero',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
