import docs from './video.docs.mdx';

import VideoTemplate from '@theme/patterns/components/video.html.twig';

import { editableField } from '@theme/snippets';

import video from '@theme/data/video.data';

export const BaseIframe = () => VideoTemplate(video);
export const BaseHosted = () => VideoTemplate(
  Object.assign(
    {},
    video,
    {
      video_type: 'hosted',
      video_src: '/images/sample-mp4-file.mp4'
    }
  )
);

export default {
  title: 'Components / Video',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
