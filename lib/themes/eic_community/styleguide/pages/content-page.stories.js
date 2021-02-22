export default {
  title: 'Pages / Content Page',
};

import contentPageFull from '@theme/patterns/pages/content-page/content-page.full.html.twig';

import EditableHeroBannerTemplate from '@theme/patterns/compositions/editable-hero-banner.html.twig';
import FeaturedOverviewTemplate from '@theme/patterns/compositions/featured-content-collection.html.twig';
import HeaderExampleTemplate from '@theme/snippets/header-example.html.twig';
import TextBlockTemplate from '@theme/patterns/compositions/text-block.html.twig';
import MediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import featuredOverview from '@theme/data/featured-content-collection';
import introductionHeader from '@theme/data/introduction-header.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import textBlock from '@theme/data/text-block.data';

export const Full = () =>
  contentPageFull({
    site_header: siteHeader,
    site_footer: siteFooter,
    introduction_header: introductionHeader,
    items: [
      {
        content: TextBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-section-wrapper--is-grey',
            },
            textBlock
          )
        ),
      },
      {
        content: FeaturedOverviewTemplate(featuredOverview.card),
      },
      {
        content: MediaWrapperTemplate({
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
        content: TextBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-text-block--is-reversed',
            },
            textBlock
          )
        ),
      },
      {
        content: EditableHeroBannerTemplate({
          extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-blue',
          image: 'https://picsum.photos/1600/450',
          content: HeaderExampleTemplate(),
        }),
      },
      {
        content: TextBlockTemplate(
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
