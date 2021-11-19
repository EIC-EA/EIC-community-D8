import docs from './topics.docs.mdx';
import topicListTemplate from '@theme/patterns/compositions/topic/topic.overview.list.html.twig';
import topicTeaserTemplate from '@theme/patterns/compositions/topic/topic.overview.subitem.html.twig';
import common from '@theme/data/common.data';
import {mockItems, randomString} from "../../snippets";

const teaserItem = {
  "title": randomString(),
  "url": "#",
  "image": {
    src: 'https://picsum.photos/200/200',
  },
  "stats": {
    groups: {
      stat: 10,
      icon: {
        type: "custom",
        name: "group"
      }
    },
    experts: {
      stat: 3,
      icon: {
        type: "custom",
        name: "user_circle"
      }
    }
  }
}
const topicListItem = {
  icon_file_path: common.icon_file_path,
  topics: [
    {
      title: "Horizontal",
      items: mockItems(teaserItem, 10)
    }
  ]
}

export const TopicsList = () => topicListTemplate(topicListItem)
export const TopicTeaserItem = () => topicTeaserTemplate({...teaserItem, icon_file_path: common.icon_file_path,})

export default {
  title: 'Compositions / Topics list',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
