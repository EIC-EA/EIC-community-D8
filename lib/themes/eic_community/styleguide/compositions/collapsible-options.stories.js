import docs from './collapsible-options.docs.mdx';

import LinkTemplate from '@ecl-twig/ec-component-link/ecl-link.html.twig';
import collapsibleOptionsTemplate from '@theme/patterns/compositions/collapsible-options.html.twig';

import common from '@theme/data/common.data';

export const Links = () =>
  collapsibleOptionsTemplate({
    collapse_label: 'More links',
    icon_file_path: common.icon_file_path,
    items: [
      {
        link: {
          label: 'New Story',
          path: '?path=new-story',
        },
        icon: {
          name: 'news',
          type: 'custom',
        },
      },
      {
        link: {
          label: 'New Event',
          path: '?path=new-event',
        },
        icon: {
          name: 'calendar',
          type: 'custom',
        },
      },
    ],
  });

export const Buttons = () =>
  collapsibleOptionsTemplate({
    collapse_label: 'More Buttons',
    icon_file_path: common.icon_file_path,
    items: [
      {
        label: 'New Story',
        icon: {
          name: 'news',
          type: 'custom',
        },
      },
      {
        label: 'New Event',
        icon: {
          name: 'calendar',
          type: 'custom',
        },
      },
    ],
  });

export const CustomTrigger = () =>
  collapsibleOptionsTemplate({
    icon_file_path: common.icon_file_path,
    trigger: LinkTemplate({
      link: {
        label: 'ECL link as Custom Trigger',
        path: '#',
      },
    }),
    items: [
      {
        label: 'New Story',
        icon: {
          name: 'news',
          type: 'custom',
        },
      },
      {
        label: 'New Event',
        icon: {
          name: 'calendar',
          type: 'custom',
        },
      },
    ],
  });

export default {
  title: 'Compositions / Collapsible Options',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
