export default {
  title: 'Compositions / Media Wrapper',
};

import mediaWrapperTemplate from '~/patterns/compositions/media-wrapper.html.twig';

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
