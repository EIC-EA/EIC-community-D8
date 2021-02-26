import common from '@theme/data/common.data';

export default {
  icon_file_path: common.icon_file_path,
  logo: {
    title: 'European Commission',
    alt: 'European Commission logo',
    href: '/example',
    src_desktop: 'https://ec.europa.eu/info/sites/info/themes/europa/images/svg/logo/logo--en.svg',
  },
  login: {
    link: {
      label: 'Log in',
      path: '?path=login',
    },
  },
  language_selector: {
    overlay: {
      close_label: 'Close',
      title: 'Select your language',
      items: [
        { lang: 'bg', label: 'български', path: '/example#language_bg' },
        { lang: 'es', label: 'español', path: '/example#language_es' },
        { lang: 'en', label: 'english', path: '/example#language_en', active: true },
      ],
    },
    href: '?path=langauge',
    name: 'English',
    code: 'En',
  },
  login_toggle: {},
  login_box: {},
  search_toggle: {
    label: 'Search',
    href: '#',
  },
  search_form: {},
  extra_classes: '',
  extra_attributes: [],
};
