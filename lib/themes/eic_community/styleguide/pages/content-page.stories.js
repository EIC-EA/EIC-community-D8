export default {
  title: 'Pages / Content Page',
};

import contentPageFull from '@theme/patterns/pages/content-page/content-page.full.html.twig';

import featuredOverviewTemplate from '@theme/patterns/compositions/featured-content-collection.html.twig';
import textBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';
import mediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import common from '@theme/data/common.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import textBlock from '@theme/data/text-block.data';
import introductionHeader from '@theme/data/introduction-header.data';
import featuredOverview from '@theme/data/featured-content-collection';

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
              extra_classes: 'ecl-section-wrapper--is-grey',
            },
            textBlock
          )
        ),
      },
      {
        content: mediaWrapperTemplate({
          extra_classes: 'ecl-section-wrapper',
          image: 'https://picsum.photos/1600/384',
          alt: 'Full width media example',
        }),
      },
      {
        content: featuredOverviewTemplate(featuredOverview.card),
      },
      {
        content: mediaWrapperTemplate({
          icon_file_path: common.icon_file_path,
          extra_classes: 'ecl-section-wrapper',
          image: 'https://picsum.photos/1600/900',
          alt: 'Video example',
          description:
            'Nulla irure ex commodo exercitation duis labore incididunt officia enim sunt dolor sunt nulla officia.',
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
              extra_classes: 'ecl-section-wrapper--is-grey',
            });
          })()
        ),
      },
    ],
  });
