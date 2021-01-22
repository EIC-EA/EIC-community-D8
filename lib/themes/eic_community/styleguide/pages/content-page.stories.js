export default {
  title: 'Pages / Content Page',
};

import contentPageFull from '@theme/patterns/pages/content-page/content-page.full.html.twig';

import cardOverviewTemplate from '@theme/patterns/compositions/card-overview.html.twig';
import textBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';
import mediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import siteFooter from '@theme/data/site-footer.data.js';
import siteHeader from '@theme/data/site-header.data.js';
import textBlock from '@theme/data/text-block.data.js';
import introductionHeader from '@theme/data/introduction-header.data.js';
import cardOverview from '../../data/card-overview.data';

export const Full = () =>
  contentPageFull({
    site_header: siteHeader,
    site_footer: siteFooter,
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
        content: cardOverviewTemplate(cardOverview),
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
