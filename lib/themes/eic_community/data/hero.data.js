import common from '@theme/data/common.data';

import authorTemplate from '@theme/patterns/components/author.html.twig';
import readTimeTemplate from '@theme/patterns/components/timestamp.html.twig';

export default {
  image: {
    src: 'https://picsum.photos/1600/500',
    alt: 'Culpa minim culpa quis tempor id pariatur exercitation ea culpa nisi tempor mollit.',
  },
  items: [
    {
      content: authorTemplate({
        name: 'John Doe',
      }),
    },
    {
      content: readTimeTemplate({
        label: '8 minutes read',
        icon_file_path: common.icon_file_path,
      }),
    },
  ],
};
