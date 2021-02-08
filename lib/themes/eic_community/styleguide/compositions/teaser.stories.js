import docs from './teaser.docs.mdx';

import eventTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import groupTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import memberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import organisationTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import storyTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';

import teaser from '@theme/data/teaser';

export const EventTeaser = () => eventTemplate(teaser.event);

export const GroupTeaser = () => groupTemplate(teaser.group);

export const MemberTeaser = () => memberTemplate(teaser.member);

export const OrganisationTeaser = () => organisationTemplate(teaser.organisation);

export const StoryTeaser = () => storyTemplate(teaser.story);

export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
