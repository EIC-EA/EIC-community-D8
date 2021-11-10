import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  type: 'radio',
  label: 'I want to start',
  helper_id: 'radio-default-helper',
  name: 'radio-binary',
  invalid: false,
  binary: true,
  items: [
    {
      id: 'type-1',
      value: 'idea',
      label: 'Idea',
      icon: {
        name: 'idea',
        type: 'custom',
      },
    },
    {
      id: 'type-2',
      value: 'question',
      label: 'Question',
      icon: {
        name: 'question',
        type: 'custom',
      },
    },
    {
      id: 'type-3',
      value: 'discussion',
      label: 'Discussion',
      icon: {
        name: 'discussion',
        type: 'custom',
      },
    },
  ],
}
