import commonData from './common.data';

export const checkboxes = [
  {
    name: 'checkbox-default',
    items: [
      {
        id: 'checkbox-default--be',
        value: 'be',
        label: 'België',
      },
    ],
  },
  {
    name: 'checkbox-default',
    items: [
      {
        id: 'checkbox-default--nl',
        value: 'nl',
        label: 'Nederland',
      },
    ],
  },
  {
    name: 'checkbox-default',
    items: [
      {
        id: 'checkbox-default--lu',
        value: 'lu',
        label: 'Luxembourg',
      },
    ],
  },
  {
    name: 'checkbox-default',
    items: [
      {
        id: 'checkbox-default--de',
        value: 'de',
        label: 'Deutschland',
      },
    ],
  },
  {
    id: 'checkbox-default--fr',
    name: 'checkbox-default',
    items: [
      {
        value: 'fr',
        label: 'France',
      },
    ],
  },
  {
    id: 'checkbox-default--es',
    name: 'checkbox-default',
    items: [
      {
        value: 'es',
        label: 'España',
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
