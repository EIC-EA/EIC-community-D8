import docs from './organisation-information-block.docs.mdx';

import OrganisationInformationBlockTemplate from '@theme/patterns/compositions/organisation-information-block.html.twig';

import { editableField } from '@theme/snippets';

import organisationInformationBlock from '@theme/data/organisation-information-block.data';

export const Base = () => OrganisationInformationBlockTemplate(organisationInformationBlock);

export default {
  title: 'Compositions / Organisation Information Block',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
