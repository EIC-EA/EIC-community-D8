import AddressTemplate from '@theme/patterns/components/address.html.twig';

import { editableField } from '@theme/snippets';

import common from '@theme/data/common.data';

export default {
  title: 'John Doe',
  description: editableField(),
  icon_file_path: common.icon_file_path,
  image: {
    src: 'https://picsum.photos/200',
  },
  type: {
    label: 'Group Administrator',
    icon: {
      name: 'star_circle',
      type: 'custom',
    },
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
  job_titles: [
    {
      label: 'CEO -',
      items: [
        {
          label: 'Boxface inc.',
          path: 'https://example.com',
        },
      ],
    },
  ],
  stats: [
    {
      icon: {
        name: 'time',
        type: 'custom',
      },
      label: 'Joined March 2019',
    },
    {
      label: '6 followers',
      icon: {
        name: 'follow',
        type: 'custom',
      },
    },
  ],
  sidebar_title: 'Contact information',
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
      label: 'E-mail',
      items: [
        {
          label: 'johndoe@example.com',
          path: 'mailto?info@example.com',
        },
      ],
    },
    {
      label: 'Company',
      items: [
        {
          content: AddressTemplate({
            content: `
              Boxface Inc.<br/>
              Streetname 103<br/>
              1034 Brussel<br/>
              Belgium<br/>
            `,
          }),
        },
      ],
    },
  ],
};
