import docs from './media-wrapper.docs.mdx';

import MediaWrapperTemplate from '@theme/patterns/compositions/media-wrapper.html.twig';

import common from '@theme/data/common.data';
import { embedField } from '@theme/snippets';

export const Base = () =>
  MediaWrapperTemplate({
    image: 'https://picsum.photos/1600/500',
    alt: 'Avatar image of Jane Doe',
  });

export const Video = () =>
  MediaWrapperTemplate({
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

export const EmbeddedMedia = () =>
  MediaWrapperTemplate({
    image: 'https://picsum.photos/1600/500',
    alt: 'Avatar image of Jane Doe',
    icon_file_path: common.icon_file_path,
    embedded_media: embedField('https://www.youtube.com/embed/fgi-GSCB6ho'),
  });

export default {
  title: 'Compositions / Media Wrapper',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
