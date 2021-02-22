import common from '@theme/data/common.data';

export default {
  title: 'Key Benefit and USP of the page detail page',
  title_element: 'h2',
  meta: [{ label: 'Benefits' }],
  call_to_action: {
    link: {
      type: 'standalone',
      label: 'Standalone link',
      path: 'http://google.com',
      icon_position: 'after',
      aria_label: 'An aria label',
    },
  },
  icon_file_path: common.icon_file_path,
  media: {
    image: 'http://placehold.it/320/240',
    alt: 'Alternate text',
    sources: [
      {
        type: 'video/mp4',
        src: 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
      },
    ],
  },
};
