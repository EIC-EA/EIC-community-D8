import common from '@theme/data/common.data';

export default {
  text_input: {
    id: 'input-search',
    name: 'search',
    extra_classes: 'ecl-search-form__text-input',
  },
  button: {
    variant: 'search',
    icon: {
      type: 'general',
      name: 'search',
      path: common.icon_file_path,
    },
    label: 'Search',
    extra_classes: 'ecl-search-form__button',
  },
};
