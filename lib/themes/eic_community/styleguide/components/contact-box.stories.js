import docs from "./contact-box.docs.mdx";
import ContactBoxTemplate from '@theme/patterns/components/contact-box.html.twig';

export const Base = () => ContactBoxTemplate({
  title: 'Didn’t find what you were looking for?',
  body: 'Contact us at</br><a class="" href="mailto:support@eic.com">support@eic.com</a>',
  cta: {
    label: 'Contact us',
    link: 'mailto:support@eic.com'
  }
});

export default {
  title: 'Components / Contact box',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
