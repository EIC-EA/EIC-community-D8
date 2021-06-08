import docs from './auth-banner.docs.mdx';

import StateBannerTemplate from '@theme/patterns/compositions/state-banner.html.twig';

import common from '@theme/data/common.data';
import { editableField } from '@theme/snippets';

export const Base = () =>
  StateBannerTemplate({
    title: 'Awaiting approval',
    description: editableField(),
    icon_file_path: common.icon_file_path,
    icon: {
      name: 'alert',
      type: 'custom',
    },
  });

export const CanDismiss = () =>
  StateBannerTemplate({
    title: 'Awaiting approval',
    description: editableField(),
    icon_file_path: common.icon_file_path,
    dismiss: {
      label: "Don't show again",
    },
    icon: {
      name: 'alert',
      type: 'custom',
    },
  });

export default {
  title: 'Compositions / State Banner',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
