import docs from './text-block.docs.mdx';

import textBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';
import textBlock from '@theme/data/text-block.data';

export const Base = () => textBlockTemplate(textBlock);
export const Reversed = () =>
  textBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-text-block--is-reversed',
      },
      textBlock
    )
  );

export const Blue = () =>
  textBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-section-wrapper--is-blue',
      },
      textBlock
    )
  );

export const Grey = () =>
  textBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-section-wrapper--is-grey',
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
