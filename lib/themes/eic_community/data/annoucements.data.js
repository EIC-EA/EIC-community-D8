import {editableField, mockItems} from "../snippets";
import common from '@theme/data/common.data';

export default [
  {
    title: 'We are looking for',
    extra_classes: 'ecl-featured-list--is-organisation-announcements',
    title_element: 'h4',
    icon_file_path: common.icon_file_path,
    is_collapsible: true,
    collapse_label: 'Show 2 more',
    items: mockItems({
      title: 'What we offering annoucement title',
      description: editableField(),
      cta: {
        link: 'mailto:example@easme.be',
        label: 'Contact us'
      }
    }, 4)
  },
  {
    title: 'What we offer',
    extra_classes: 'ecl-featured-list--is-organisation-announcements',
    title_element: 'h4',
    icon_file_path: common.icon_file_path,
    is_collapsible: true,
    collapse_label: 'Show 2 more',
    items: mockItems({
      title: 'What we offering annoucement title',
      description: editableField(),
      cta: {
        link: 'mailto:example@easme.be',
        label: 'Contact us'
      }
    }, 4)
  }
]
