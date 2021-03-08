import docs from './mainmenu.docs.mdx';

import MainmenuTemplate from '@theme/patterns/compositions/mainmenu.html.twig';

import mainmenu from '@theme/data/mainmenu.data';
import searchform from '@theme/data/searchform.data';

export const Base = () =>
  MainmenuTemplate({
    mainmenu: mainmenu,
  });

export const Labeled = () =>
  MainmenuTemplate({
    label: 'EIC community page',
    mainmenu: mainmenu,
  });

export const WithSearchform = () =>
  MainmenuTemplate({
    mainmenu: mainmenu,
    searchform: searchform,
  });

export default {
  title: 'Compositions / Mainmenu',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
