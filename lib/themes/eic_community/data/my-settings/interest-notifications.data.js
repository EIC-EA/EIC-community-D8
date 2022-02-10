import { editableField } from '@theme/snippets';
import EmailNotificationTemplate from '@theme/patterns/compositions/member/email-notification.html.twig';
import common from '@theme/data/common.data';


export default (title) => ({
  title: title,
  content: EmailNotificationTemplate({
    title,
    body: 'By indication thematic of geographic interests, you are automatically subscribed to a periodic notification email bringing together the latest highlighted items.',
    icon_file_path: common.icon_file_path,
    action: {
      link: {
        label: 'Edit interests',
        path: '?path=Edit interests',
      },
      extra_classes: "ecl-link--button ecl-link--button-secondary ",
      variant: 'secondary',
      icon_position: "before",
      icon: {
        name: 'gear',
        type: 'custom',
      },
    },
  })
});
