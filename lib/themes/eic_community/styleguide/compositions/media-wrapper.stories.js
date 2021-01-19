import docs from './media-wrapper.docs.mdx';

import mediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

export const Base = () =>
  mediaWrapperTemplate({
    image: 'http://placehold.it/144x144',
    alt: 'Avatar image of Jane Doe',
  });

export const Video = () =>
  mediaWrapperTemplate({
    image: 'http://placehold.it/144x144',
    alt: 'Avatar image of Jane Doe',
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
