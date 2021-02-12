import docs from './fact-figures-wrapper.docs.mdx';

import factFiguresTemplate from '@theme/patterns/compositions/fact-figures-wrapper.html.twig';

import factFigures from '@theme/data/fact-figures.data';

export const Base = () => factFiguresTemplate(factFigures);

export default {
  title: 'Compositions / Fact Figures Wrapper',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
