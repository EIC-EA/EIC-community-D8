import EmailNotificationTemplate from '@theme/patterns/compositions/member/email-notification.html.twig';
import common from '@theme/data/common.data';
import ListTagsData from '@theme/data/list-tags.data.js';



export default (title) => ({
  title: title,
  content: EmailNotificationTemplate({
    title,
    body: 'By indication thematic of geographic interests, you are automatically subscribed to a periodic notification email bringing together the latest highlighted items.',
    icon_file_path: common.icon_file_path,
    global_action: {
      title: 'Comments email notifications',
      state: true
    }
  })
});
