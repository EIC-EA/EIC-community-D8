import docs from './contact-information-block.docs.mdx';

import MemberInformationBlockTemplate from '@theme/patterns/compositions/member-information-block.html.twig';
import OrganisationInformationBlockTemplate from '@theme/patterns/compositions/contact-information-block.html.twig';

import memberInformationBlock from '@theme/data/member-information-block.data';
import organisationInformationBlock from '@theme/data/contact-information-block.data';

export const MemberInformationBlock = () => MemberInformationBlockTemplate(memberInformationBlock);

export const OrganisationInformationBlock = () =>
  OrganisationInformationBlockTemplate(organisationInformationBlock);

export default {
  title: 'Compositions / Contact Information Block',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
