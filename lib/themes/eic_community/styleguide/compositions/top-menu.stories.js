import docs from './top-menu.docs.mdx';

import TopMenuTemplate from '@theme/patterns/compositions/top-menu.html.twig';

import topMenu from '@theme/data/top-menu.data';

export const Base = () => TopMenuTemplate(topMenu);

export default {
  title: 'Compositions / Top Menu',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
