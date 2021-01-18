export default {
  sections: [
    {
      title: {
        link: {
          label: 'European Commission website',
          path: '#',
        },
      },
      description: 'This site is managed by the Directorate-General for Communication',
    },
    {
      section_class_name: 'ecl-footer-core__section--separator',
      links: [
        {
          link: {
            label: 'Standalone link',
            path: 'http://google.com',
          },
        },
      ],
      list_class_name: 'ecl-footer-core__list--columns',
    },
    {
      links: [
        {
          link: {
            label: 'Follow the European Commission on social media',
            path: '/example',
            icon_position: 'after',
          },
        },
      ],
    },
    {
      links: [
        {
          link: {
            label: 'Language policy',
            path: '/example',
          },
        },
      ],
    },
  ],
};
