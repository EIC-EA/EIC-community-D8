import EmailNotificationTemplate from '@theme/patterns/compositions/member/email-notification.html.twig';
import common from '@theme/data/common.data';
import NotifManagementTemplate from '@theme/patterns/compositions/notifications-management.html.twig';
import pagination from '@theme/data/pagination.data';

import { mockItems } from '@theme/snippets';


export default (title) => ({
  title: title,
  content: EmailNotificationTemplate({
    title,
    body: 'By indication thematic of geographic interests, you are automatically subscribed to a periodic notification email bringing together the latest highlighted items.',
    icon_file_path: common.icon_file_path,
    table: NotifManagementTemplate({
      title: 'Topics',
      icon_file_path: common.icon_file_path,
      unsubscribe: true,
      demo: true,
      pagination: pagination,
    }),

  })
});
