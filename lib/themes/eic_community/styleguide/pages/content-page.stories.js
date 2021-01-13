export default {
  title: 'Pages / Content Page',
};

import contentPageTemplate from '~/patterns/pages/content-page.html.twig';
import textBlockTemplate from '~/patterns/compositions/text-block.html.twig';
import mediaWrapperTemplate from '~/patterns/compositions/media-wrapper.html.twig';

import siteHeader from '~/data/site-header.data.js';
import textBlock from '~/data/text-block.data.js';
import introductionHeader from '~/data/introduction-header.data.js';

export const Base = () =>
  contentPageTemplate({
    site_header: siteHeader,
    introduction_header: introductionHeader,
    items: [
      {
        content: textBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-text-block--is-shaded',
            },
            textBlock
          )
        ),
      },
      {
        content: mediaWrapperTemplate({
          image: 'http://placehold.it/144x144',
          alt: 'Avatar image of Jane Doe',
          sources: [
            {
              type: 'video/mp4',
              src:
                'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            },
          ],
        }),
      },
      {
        content: textBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-text-block--is-reversed',
            },
            textBlock
          )
        ),
      },
      {
        content: textBlockTemplate(
          (() => {
            const { media, ...r } = textBlock;

            return Object.assign(r, {
              extra_classes: 'ecl-text-block--is-shaded',
            });
          })()
        ),
      },
    ],
  });
