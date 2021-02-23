import docs from './organisation-information-block.docs.mdx';

import ContentTypeInformationBlockTemplate from '@theme/patterns/compositions/organisation-information-block.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () => ContentTypeInformationBlockTemplate();

export default {
  title: 'Compositions / Content type Information Block',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
