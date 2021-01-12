export default {
  title: "Pages / Content Page"
}

import contentPageTemplate from '~/patterns/pages/content-page.html.twig';
import textBlockTemplate from '~/patterns/compositions/text-block.html.twig';

import siteHeader from '~/data/site-header.data.js';
import textBlock from '~/data/text-block.data.js';
import introductionHeader from '~/data/introduction-header.data.js';

export const Base = () => contentPageTemplate({
  site_header: siteHeader,
  introduction_header: introductionHeader,
  items: [
    {
      content: textBlockTemplate(Object.assign({
        extra_classes: 'ecl-text-block--is-shaded',
      }, textBlock)),
    },
    {
      content: textBlockTemplate(Object.assign({
        extra_classes: 'ecl-text-block--is-reversed',
      }, textBlock)),
    },
    {
      content: textBlockTemplate((() => {
        const { media, ...r } = textBlock

        return Object.assign(r, {
          extra_classes: 'ecl-text-block--is-shaded',
        });
      })())
    },
  ]
})
