import docs from './address.docs.mdx';

import AddressTemplate from '@theme/patterns/components/address.html.twig';

export const Base = () =>
  AddressTemplate({
    content: `
      Boxface Inc.<br/>
      Streetname 103<br/>
      1034 Brussel<br/>
      Belgium<br/>
    `,
  });

export default {
  title: 'Components / Address',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
