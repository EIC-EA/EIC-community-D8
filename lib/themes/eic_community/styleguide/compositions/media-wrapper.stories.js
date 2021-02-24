import docs from './media-wrapper.docs.mdx';

import mediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  mediaWrapperTemplate({
    image: 'https://picsum.photos/1600/500',
    alt: 'Avatar image of Jane Doe',
  });

export const Video = () =>
  mediaWrapperTemplate({
    image: 'https://picsum.photos/1600/500',
    alt: 'Avatar image of Jane Doe',
    icon_file_path: common.icon_file_path,
    sources: [
      {
        type: 'video/mp4',
        src: 'http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
      },
    ],
  });

export default {
  title: 'Compositions / Media Wrapper',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
