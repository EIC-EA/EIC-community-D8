import common from '@theme/data/common.data';

export default {
  icon_path: common.icon_file_path,
  label: 'Horizontal Topics',
  type: 'select',
  options: [
    {
      value: -1,
      label: '- Select a topic -',
    },
    {
      value: 1,
      label: 'Accessibility',
    },
    {
      value: 2,
      label: 'Privacy',
    },
    {
      value: 3,
      label: 'Press related',
    },
    {
      value: 4,
      label: 'General Help',
    },
    {
      value: 4,
      label: 'Website related feedback',
    },
  ],
  helper_text: 'Please select one or more categories.',
  id: 'feedback-category',
  name: 'feedback-category',
}
