import PageTemplate from '@theme/patterns/pages/page/page.full.html.twig';
import FormFormatterTemplate from '@theme/patterns/components/form/form-formatter.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import form from '@theme/data/form';

export const Contact = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
    searchform: searchform,
    title: 'Contact Form',
    breadcrumb: breadcrumb,
    image: {
      src: 'https://picsum.photos/1600/400',
    },
    content: FormFormatterTemplate(
      Object.assign(
        {
          icon_file_path: common.icon_file_path,
        },
        form.contact
      )
    ),
  });

export const Discussion = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
    searchform: searchform,
    title: 'Feedback',
    breadcrumb: breadcrumb,
    image: {
      src: 'https://picsum.photos/1600/400',
    },
    content: FormFormatterTemplate(
      Object.assign(
        {
          icon_file_path: common.icon_file_path,
        },
        form.discussion
      )
    ),
  });

export const Feedback = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
    searchform: searchform,
    title: 'Feedback',
    breadcrumb: breadcrumb,
    image: {
      src: 'https://picsum.photos/1600/400',
    },
    content: FormFormatterTemplate(
      Object.assign(
        {
          icon_file_path: common.icon_file_path,
        },
        form.feedback
      )
    ),
  });

export const Groups = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
    searchform: searchform,
    title: 'Group Form',
    breadcrumb: breadcrumb,
    image: {
      src: 'https://picsum.photos/1600/400',
    },
    content: FormFormatterTemplate(
      Object.assign(
        {
          icon_file_path: common.icon_file_path,
        },
        form.group
      )
    ),
  });

export default {
  title: 'Bundles / Forms',
};
