import docs from './teaser.docs.mdx';

import EventTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import GroupTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import MemberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import ProjectTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';
import OrganisationTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import StoryTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import teaser from '@theme/data/teaser';

export const EventTeaser = () => EventTemplate(teaser.event);

export const GroupTeaser = () => GroupTemplate(teaser.group);

export const MemberTeaser = () => MemberTemplate(teaser.member);

export const ProjectTeaser = () => ProjectTemplate(teaser.project);

export const OrganisationTeaser = () => OrganisationTemplate(teaser.organisation);

export const StoryTeaser = () => StoryTemplate(teaser.story);

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
