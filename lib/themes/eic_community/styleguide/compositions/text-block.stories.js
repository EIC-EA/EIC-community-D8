import docs from './text-block.docs.mdx';

import TextBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';

import textBlock from '@theme/data/text-block.data';

export const Base = () => TextBlockTemplate(textBlock);

export const Reversed = () =>
  TextBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-section-wrapper ecl-text-block--is-reversed',
      },
      textBlock
    )
  );

export const Blue = () =>
  TextBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-section-wrapper--is-blue',
      },
      textBlock
    )
  );

export const Grey = () =>
  TextBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-section-wrapper ecl-section-wrapper--is-grey',
      },
      textBlock
    )
  );

export default {
  title: 'Compositions / Text Block',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
