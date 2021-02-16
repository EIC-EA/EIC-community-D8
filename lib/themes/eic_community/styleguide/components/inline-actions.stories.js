import docs from './inline-actions.docs.mdx';

import inlineActionsTemplate from '@theme/patterns/components/inline-actions.html.twig';

export const Base = () =>
  inlineActionsTemplate({
    items: [
      {
        link: {
          label: 'Login',
          path: '?path=login',
        },
      },
      {
        link: {
          label: 'Register',
          path: '?path=register',
        },
      },
    ],
  });

export default {
  title: 'Components / Inline actions',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
