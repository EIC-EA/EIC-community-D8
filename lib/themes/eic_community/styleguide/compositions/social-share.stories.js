import docs from './social-share.docs.mdx';

import SocialShareTemplate from '@theme/patterns/compositions/social-share.html.twig';

import socialShare from '@theme/data/social-share.data';

export const Base = () => SocialShareTemplate(socialShare);

export const Small = () =>
  SocialShareTemplate(
    Object.assign(
      {
        compact: true,
      },
      socialShare
    )
  );

export default {
  title: 'Compositions / Social Share',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
