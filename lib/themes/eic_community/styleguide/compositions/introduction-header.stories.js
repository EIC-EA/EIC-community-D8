import docs from './introduction-header.docs.mdx';

import IntroductionHeaderTemplate from '@theme/patterns/compositions/introduction-header.html.twig';

import introductionHeader from '@theme/data/introduction-header.data.js';

export const Base = () => IntroductionHeaderTemplate(introductionHeader);

export const Reversed = () =>
  IntroductionHeaderTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-introduction-header--is-reversed',
      },
      introductionHeader
    )
  );

export default {
  title: 'Compositions / Introduction Header',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
