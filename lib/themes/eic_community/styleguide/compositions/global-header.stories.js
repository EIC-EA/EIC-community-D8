import docs from './global-header.docs.mdx';

import GlobalHeaderTemplate from '@theme/patterns/compositions/global-header.html.twig';

import siteHeader from '@theme/data/site-header.data';
import common from '@theme/data/common.data';

export const Public = () => GlobalHeaderTemplate(siteHeader);

export const Private = () =>
  GlobalHeaderTemplate(
    Object.assign(
      {
        user: common.user,
      },
      siteHeader
    )
  );

export default {
  title: 'Compositions / Global Header',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
