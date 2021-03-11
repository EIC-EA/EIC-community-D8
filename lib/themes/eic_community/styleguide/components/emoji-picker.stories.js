import docs from './emoji-picker.docs.mdx';

import EmojiPickerTemplate from '@theme/patterns/components/emoji-picker.html.twig';

import common from '@theme/data/common.data';

export const Base = () => `
  ${EmojiPickerTemplate({
    label: 'Insert Emoji',
    target: '#textarea',
    icon_file_path: common.icon_file_path,
  })}

  <textarea id="textarea"></textarea>
`;

export default {
  title: 'Components / Emoji Picker',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
