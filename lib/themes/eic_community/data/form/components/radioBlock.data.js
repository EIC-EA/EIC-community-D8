import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  type: 'radio-block',
  label: 'group visibility & access',
  name: 'radio-block',
  invalid: false,
  items: [
    {
      id: 'type-1',
      value: 'Public',
      label: 'Public',
      text: 'This means the restricted group will be visible to every user on the platform.',
      icon: {
        name: 'group',
        type: 'custom',
      },
    },
    {
      id: 'type-2',
      value: 'Community members only',
      label: 'Community members only',
      text: 'This means the restricted group will be visible to each trusted user on the platform. ',
      icon: {
        name: 'lock',
        type: 'custom',
      },
    },
    {
      id: 'type-3',
      value: 'Open',
      label: 'Open',
      text: 'Community members can join immediately.',
    }
  ],
}
