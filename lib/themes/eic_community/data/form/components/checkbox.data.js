import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  type: 'checkbox',
  label: 'I want to start',
  helper_id: 'checkbox-default-helper',
  name: 'checkbox-binary',
  invalid: false,
  binary: true,
  icon: {
    name: 'idea',
    type: 'custom',
  },
  items: [
    {
      id: 'type-1',
      value: 'idea',
      label: 'Idea',
    },
    {
      id: 'type-2',
      value: 'question',
      label: 'Question',
    },
    {
      id: 'type-3',
      value: 'discussion',
      label: 'Discussion',
    },
  ],
}
