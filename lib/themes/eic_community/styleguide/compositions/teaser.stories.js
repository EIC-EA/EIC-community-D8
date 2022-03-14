import docs from './teaser.docs.mdx';

import EventTemplate from '@theme/patterns/compositions/event/event.teaser.html.twig';
import FilelistTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';
import GalleryTemplate from '@theme/patterns/compositions/gallery/gallery.teaser.html.twig';
import GroupTemplate from '@theme/patterns/compositions/group/group.teaser.html.twig';
import MemberTemplate from '@theme/patterns/compositions/member/member.teaser.html.twig';
import ProjectTemplate from '@theme/patterns/compositions/project/project.teaser.html.twig';
import OrganisationTemplate from '@theme/patterns/compositions/organisation/organisation.teaser.html.twig';
import OrganisationMemberTemplate from '@theme/patterns/compositions/organisation/organisation.member.teaser.html.twig';
import StoryTemplate from '@theme/patterns/compositions/story/story.teaser.html.twig';
import VideoTemplate from '@theme/patterns/compositions/video/video.teaser.html.twig';
import wikiTemplate from '@theme/patterns/compositions/wiki/wiki.teaser.html.twig';


import teaser from '@theme/data/teaser';
import organisation_member from "../../data/teaser/organisation_member.data";

export const WikiTeaser = () => wikiTemplate(teaser.wiki)

export const EventTeaser = () => EventTemplate(teaser.event);

export const FilelistTeaser = () => FilelistTemplate(teaser.filelist);

export const FilelistTeaserHighlight = () => FilelistTemplate(
  Object.assign(
    {},
    teaser.filelist,
    {
      highlight: {
        is_active: true,
        label: 'Highlight',
        path: '#'
      },
    }
  )
);

export const GalleryTeaser = () => GalleryTemplate(teaser.gallery);

export const GalleryTeaserHighlight = () => GalleryTemplate(
  Object.assign(
    {},
    teaser.gallery,
    {
      highlight: {
        is_active: true,
        label: 'Highlight',
        path: '#'
      },
    }
  )
);
console.log(teaser.organisation_member, { ...teaser.organisation_member, path: '' });

export const GroupTeaser = () => GroupTemplate(teaser.group);

export const MemberTeaser = () => MemberTemplate(teaser.member);

export const ProjectTeaser = () => ProjectTemplate(teaser.project);

export const OrganisationTeaser = () => OrganisationTemplate(teaser.organisation);

export const OrganisationMemberTeaserWhitoutPath = () => OrganisationMemberTemplate({ ...teaser.organisation_member, organisations: [{ ...teaser.organisation_member.organisations[0], path: '' }] });

export const OrganisationMemberTeaser = () => OrganisationMemberTemplate(teaser.organisation_member);

export const StoryTeaser = () => StoryTemplate(teaser.story);

export const VideoTeaser = () => VideoTemplate(teaser.video);

export const VideoTeaserHighlight = () => VideoTemplate(
  Object.assign(
    {},
    teaser.video,
    {
      highlight: {
        is_active: true,
        label: 'Highlight',
        path: '#'
      },
    }
  )
);


export default {
  title: 'Compositions / Teaser',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
