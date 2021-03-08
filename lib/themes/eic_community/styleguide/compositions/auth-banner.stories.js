import docs from './auth-banner.docs.mdx';

import AuthBannerTemplate from '@theme/patterns/compositions/auth-banner.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  AuthBannerTemplate({
    title: 'Login to continue.',
    icon_file_path: common.icon_file_path,
    login: {
      label: "I wan't to login",
      path: '?login',
    },
    register: {
      label: 'Sign me up!',
      path: '?register',
    },
  });

export default {
  title: 'Compositions / Auth Banner',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
