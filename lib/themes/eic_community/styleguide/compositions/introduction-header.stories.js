export default {
  title: 'Compositions / Introduction Header',
};

import introductionHeaderTemplate from '@theme/patterns/compositions/introduction-header.html.twig';
import introductionHeader from '@theme/data/introduction-header.data.js';

export const Base = () => introductionHeaderTemplate(introductionHeader);

export const Reversed = () =>
  introductionHeaderTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-introduction-header--is-reversed',
      },
      introductionHeader
    )
  );
