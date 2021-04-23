import { editableField } from '@theme/snippets';

import common from '@theme/data/common.data';

export default {
  title: 'Organisation name',
  description: editableField(),
  icon_file_path: common.icon_file_path,
  image: {
    src: 'https://logoipsum.com/logo/logo-3.svg',
  },
  follow: {
    link: {
      label: 'Follow',
      path: '?path=follow',
    },
  },
  turnover: 'annual turnover &euro; 15.632.432',
  organisation_size: '200 Employees',
  expertises_label: 'Expertise',
  expertises: [
    {
      label: 'IOT',
    },
    {
      label: 'AI',
    },
    {
      label: 'Smart Cities',
    },
  ],
  stats: [
    {
      icon: {
        name: 'time',
        type: 'custom',
      },
      label: 'Joined 2019',
    },
    {
      label: '20 active members',
      icon: {
        name: 'group',
        type: 'custom',
      },
    },
  ],
  actions: [
    {
      link: {
        label: 'About the company',
        path: '?path=about-the-company',
      },
      extra_classes: 'ecl-link--button ecl-link--button-secondary',
    },
  ],
  sidebar_title: 'Contact information',
  links: [
    {
      link: {
        label: 'Visit website',
        path: 'https:example.com',
      },
      extra_attributes: {
        target: '_blank',
      },
    },
  ],
  social_share: {
    title: 'Social media',
    size: 's',
    icon_file_path: common.icon_file_path,
    items: [
      {
        path: '?social-share=twitter',
        name: 'twitter',
        label: 'Twitter',
      },
      {
        path: '?social-share=facebook',
        name: 'facebook-current',
        label: 'Facebook',
        type: 'custom',
      },
      {
        path: '?social-share=linkedin',
        name: 'linkedin',
        label: 'LinkedIn',
      },
    ],
  },
  meta: [
    {
      label: 'Press contact',
      items: [
        {
          label: 'info@example.com',
          path: 'mailto?info@example.com',
        },
      ],
    },
    {
      label: 'Locations',
      items: [
        {
          label: 'Germany',
          path: '?path=meta-location-germany',
        },
        {
          label: 'France',
          path: '?path=meta-location-france',
        },
      ],
    },
  ],
};
