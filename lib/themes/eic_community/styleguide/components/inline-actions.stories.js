import docs from './inline-actions.docs.mdx';

import InlineActionsTemplate from '@theme/patterns/components/inline-actions.html.twig';

export const Base = () =>
  InlineActionsTemplate({
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
