import docs from './fact-figures-wrapper.docs.mdx';

import FactFiguresTemplate from '@theme/patterns/compositions/fact-figures-wrapper.html.twig';

import factFigures from '@theme/data/fact-figures.data';

export const Base = () => FactFiguresTemplate(factFigures);

export const Compact = () =>
  FactFiguresTemplate(
    Object.assign(
      {
        compact: true,
      },
      factFigures
    )
  );

export default {
  title: 'Compositions / Fact Figures Wrapper',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
