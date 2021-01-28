import docs from './social-share.docs.mdx';

import socialShareTemplate from '@theme/patterns/compositions/social-share.html.twig';
import socialShare from '@theme/data/social-share.data';

export const Base = () => socialShareTemplate(socialShare);

export default {
  title: 'Compositions / Social Share',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
