import docs from './featured-topics.docs.mdx';

import FeaturedTopicsTemplate from '@theme/patterns/compositions/featured-topics.html.twig';

import common from '@theme/data/common.data.js';
import topics from '@theme/data/topics.data.js';

import { mockItems } from '@theme/snippets';

export const Default = () => FeaturedTopicsTemplate(topics);

export const Filter = () =>
  FeaturedTopicsTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-featured-list--topics-filter',
      },
      topics
    )
  );

export const Collapsible = () =>
  FeaturedTopicsTemplate({
    title: 'Latest topics',
    is_collapsible: true,
    icon_file_path: common.icon_file_path,
    items: mockItems(
      {
        label: 'Pariatur dolor',
      },
      10
    ),
  });

export default {
  title: 'Components / Featured Topics',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
