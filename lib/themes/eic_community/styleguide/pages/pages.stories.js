import PageTemplate from '@theme/patterns/pages/page/page.full.html.twig';
import FormFormatterTemplate from '@theme/patterns/components/form/form-formatter.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import formFields from '@theme/data/form-fields.data';

export const Contact = () =>
  PageTemplate({
    common: common,
    site_header: siteHeader,
    site_footer: siteFooter,
    title: 'Contact Page',
    breadcrumb: breadcrumb,
    image: {
      src: 'https://picsum.photos/1600/400',
    },
    content: FormFormatterTemplate(
      Object.assign(
        {
          icon_file_path: common.icon_file_path,
        },
        formFields
      )
    ),
  });

export default {
  title: 'Pages / Base',
};
