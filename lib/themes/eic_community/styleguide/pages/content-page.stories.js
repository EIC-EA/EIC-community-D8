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
import common from '@theme/data/common.data';
import mainmenu from '@theme/data/mainmenu.data';
import breadcrumb from '@theme/data/breadcrumb.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import topMenu from '@theme/data/top-menu.data';
import textBlock from '@theme/data/text-block.data';

export const Full = () =>
  contentPageFull({
    breadcrumb: breadcrumb,
    site_header: siteHeader,
    site_footer: siteFooter,
    searchform: searchform,
    top_menu: topMenu,
    introduction_header: introductionHeader,
    items: [
      {
        content: TextBlockTemplate(textBlock),
      },
      {
        content: MediaWrapperTemplate({
          extra_classes: 'ecl-section-wrapper',
          image: 'https://picsum.photos/1600/384',
          alt: 'Full width media example',
        }),
      },
      {
        content: FeaturedOverviewTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-section-wrapper',
            },
            featuredOverview.card
          )
        ),
      },
      {
        content: MediaWrapperTemplate({
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
        content: TextBlockTemplate(
          Object.assign(
            {
              extra_classes: 'ecl-section-wrapper ecl-text-block--is-reversed',
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
              extra_classes: 'ecl-section-wrapper',
            });
          })()
        ),
      },
    ],
    mainmenu: mainmenu,
  });
