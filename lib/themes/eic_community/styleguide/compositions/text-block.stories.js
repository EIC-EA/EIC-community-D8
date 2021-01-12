export default {
  title: "Compositions / Text Block"
}

import textBlockTemplate from '~/patterns/compositions/text-block.html.twig';
import textBlock from '~/data/text-block.data';

export const Base = () => textBlockTemplate(textBlock)
export const Reversed = () => textBlockTemplate(Object.assign({
  extra_classes: 'ecl-text-block--is-reversed'
}, textBlock))

export const Shaded = () => textBlockTemplate(Object.assign({
  extra_classes: 'ecl-text-block--is-shaded'
}, textBlock))
