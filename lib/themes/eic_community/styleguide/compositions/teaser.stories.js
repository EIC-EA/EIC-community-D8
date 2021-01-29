import docs from './teaser.docs.mdx';

import teaserStory from '@theme/patterns/compositions/story/story.teaser.html.twig';

import common from '@theme/data/common.data';

export const StoryTeaser = () =>
  teaserStory({
    title:
      'Sunt voluptate ea proident incididunt dolore minim tempor ullamco officia nisi magna in amet.',
    description:
      'Sunt ut laborum fugiat sunt magna sint dolor ullamco laborum cupidatat eu aliqua Lorem.',
    image: {
      src: 'http://placehold.it/160x120',
    },
    timestamp: {
      label: '12 april 2010',
    },
    type: 'Story',
    icon_file_path: common.icon_file_path,
    icon: {
      type: 'custom',
      name: 'news',
    },
    author: {
      label: 'Urbanus Vliegwiel',
    },
    stats: [
      {
        value: '32',
        label: 'Comments',
        icon: {
          type: 'general',
          name: 'feedback',
        },
      },
      {
        value: '120',
        label: 'Views',
        icon: {
          type: 'custom',
          name: 'views',
        },
      },
      {
        value: '32',
        label: 'Likes',
        icon: {
          type: 'custom',
          name: 'like',
        },
      },
    ],
  });

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
