import commonData from './common.data';

export const checkboxes = [
  {
    name: 'checkbox-default',
    items: [
      {
        value: 'be',
        label: 'Belgie',
      },
    ],
  },
  {
    name: 'checkbox-default',
    items: [
      {
        value: 'nl',
        label: 'Nederland',
      },
    ],
  },
  {
    name: 'checkbox-default',
    items: [
      {
        value: 'lu',
        label: 'Luxembourg',
      },
    ],
  },
];

export const select = {
  icon_path: commonData.icon_file_path,
  options: [
    {
      value: 1,
      label: 'Red',
    },
    {
      value: 2,
      label: 'Blue',
    },
    {
      value: 3,
      label: 'Green',
    },
  ],
};

export default {
  checkboxes,
  select,
};
