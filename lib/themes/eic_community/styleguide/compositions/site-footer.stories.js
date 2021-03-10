import docs from './site-footer.docs.mdx';

import SiteFooterTemplate from '@theme/patterns/compositions/site-footer.html.twig';

import siteFooter from '@theme/data/site-footer.data';

export const Base = () => SiteFooterTemplate(siteFooter);

export default {
  title: 'Compositions / Site Footer',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
