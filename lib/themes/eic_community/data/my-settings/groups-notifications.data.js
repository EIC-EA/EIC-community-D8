import EmailNotificationTemplate from '@theme/patterns/compositions/member/email-notification.html.twig';
import common from '@theme/data/common.data';
import NotifManagementTemplate from '@theme/patterns/compositions/notifications-management.html.twig';
import pagination from '@theme/data/pagination.data';



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
    table: NotifManagementTemplate({
      title: 'Topics',
      icon_file_path: common.icon_file_path,
      items:[
        {
          name: {
            label: 'Topic 01',
            path: '#'
          },
          state: true,
          items:[
            {
              name: {
                label: 'Topic 01: subtopic 01',
                path: '#'
              },
              state: false
            },
            {
              name: {
                label: 'Topic 01: subtopic 02',
                path: '#'
              },
              state: true
            }
          ]
        },
        {
          name: {
            label: 'Topic 02',
            path: '#'
          },
          state: false,
        }
      ],
      pagination: pagination,
    }),

  })
});
