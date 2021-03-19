import PageTemplate from '@theme/patterns/pages/page/page.full.html.twig';
import FormFormatterTemplate from '@theme/patterns/components/form/form-formatter.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import contactForm from '@theme/data/contact-form.data';
import feedbackForm from '@theme/data/feedback-form.data';

export const Contact = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
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
        contactForm
      )
    ),
  });

export const Feedback = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
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
        feedbackForm
      )
    ),
  });

export default {
  title: 'Pages / Forms',
};
