export default {
  title: 'Pages / Content Page',
};

import contentPageFull from '@theme/patterns/pages/content-page/content-page.full.html.twig';

import featuredOverviewTemplate from '@theme/patterns/compositions/featured-content-collection.html.twig';
import textBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';
import mediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import textBlock from '@theme/data/text-block.data';
import introductionHeader from '@theme/data/introduction-header.data';
import featuredOverview from '@theme/data/featured-content-collection';

export const Full = () =>
  contentPageFull({
    breadcrumb: breadcrumb,
    site_header: siteHeader,
    site_footer: siteFooter,
    introduction_header: introductionHeader,
    items: [
      {
        content: textBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-section-wrapper',
            },
            textBlock
          )
        ),
      },
      {
        content: featuredOverviewTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-section-wrapper',
            },
            featuredOverview.card
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
              extra_classes: 'ecl-section-wrapper ecl-text-block--is-reversed',
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
              extra_classes: 'ecl-section-wrapper',
            });
          })()
        ),
      },
    ],
  });
