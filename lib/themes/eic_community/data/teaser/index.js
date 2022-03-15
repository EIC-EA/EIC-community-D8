import event from './event.data';
import filelist from './filelist.data';
import group from './group.data';
import member from './member.data';
import organisation from './organisation.data';
import organisation_member from './organisation_member.data';
import project from './project.data';
import search from './search.data';
import story from './story.data';
import video from './video.data';
import gallery from './gallery.data';
import wiki from './wiki.data';

export default {
  event,
  filelist,
  group,
  member,
  memberTop : {
    ...member,
    top_contributor: {
      label: 'Top contributor <b>(99k points)</b>',
      icon: {
        name: 'trophy_circle',
        type: 'custom',
      }
    }
  },
  organisation,
  organisation_member,
  project,
  search,
  story,
  video,
  gallery,
  wiki,
};
