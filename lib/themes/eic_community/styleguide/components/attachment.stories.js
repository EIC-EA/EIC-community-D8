import docs from './attachment.docs.mdx';

import AttachmentTemplate from '@theme/patterns/components/attachment.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  AttachmentTemplate({
    title: 'Et esse mollit irure do nisi laborum ad magna est non reprehenderit magna non tempor.',
    path: 'http://example.com/example-document.2020.pdf',
  });

export const Full = () =>
  AttachmentTemplate({
    title: 'Et esse mollit irure do nisi laborum ad magna est non reprehenderit magna non tempor.',
    path: 'http://example.com/example-document.2020.pdf',
    download_label: 'Download',
    language: 'English',
    filesize: '4,7 Mb',
    author: {
      author: 'John Doe',
      path: '?author=johndoe',
    },
    icon_file_path: common.icon_file_path,
    timestamp: '16 hours ago',
    icon: {
      name: 'pdf',
      type: 'custom',
    },
  });

export default {
  title: 'Components / Attachment',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
