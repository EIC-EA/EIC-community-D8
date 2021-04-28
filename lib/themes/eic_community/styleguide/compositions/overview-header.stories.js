import docs from './overview-header.docs.mdx';

import OverviewHeaderTemplate from '@theme/patterns/compositions/overview-header.html.twig';

export const Base = () =>
  OverviewHeaderTemplate({
    title:
      'Sunt deserunt in pariatur et amet sunt in commodo nulla exercitation quis enim esse cupidatat.',
  });

export const WithBackgroundImage = () =>
  OverviewHeaderTemplate({
    title: 'Et Lorem elit cupidatat minim amet cillum id irure eu.',
    image: {
      src: 'https://picsum.photos/1200/800',
    },
  });

export default {
  title: 'Compositions / Overview Header',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
