import MySettingsTemplate from '@theme/patterns/compositions/member/member.my_settings.html.twig';

import breadcrumb from '@theme/data/breadcrumb.data';
import common from '@theme/data/common.data';
import mainmenu from '@theme/data/mainmenu.data';
import searchform from '@theme/data/searchform.data';
import siteFooter from '@theme/data/site-footer.data';
import siteHeader from '@theme/data/site-header.data';
import subnavigation from '@theme/data/subnavigation';
import teaserOverview from '@theme/data/teaser-overview.data';
import topMenu from '@theme/data/top-menu.data';
import MySettingNav from '@theme/data/my-settings';


export const EmailNotifications = () =>
  MySettingsTemplate(
    {
      breadcrumb: {
        ...breadcrumb,
        ...breadcrumb.links[2].label = 'My settings'
      },
      common: common,
      mainmenu: mainmenu,
      searchform: searchform,
      subnavigation: {
        ...subnavigation.mySettings,
        ...subnavigation.mySettings.items[0].is_active = false,
        ...subnavigation.mySettings.items[1].is_active = true
      },
      amount_options: teaserOverview.amount_options,
      active_filters: teaserOverview.active_filters,
      sort_options: teaserOverview.sort_options,
      site_footer: siteFooter,
      site_header: siteHeader,
      top_menu: topMenu,
      overview_header: {
        title: 'My settings',
        extra_classes: 'ecl-overview-header--my-activity',
        icon_file_path: common.icon_file_path,
        actions: [
          {
            link: {
              label: 'My profile',
              path: '?path=direct-message',
            },
            icon: {
              name: 'gear',
              type: 'custom',
            },
          },
          {
            label: 'Post Content',
            items: [
              {
                link: {
                  label: 'New Story',
                },
              },
              {
                link: {
                  label: 'New Wiki',
                },
              },
            ],
          },
        ],
      },
      flagged_interest: MySettingNav.InterestNotification('Your interest notifications'),
      flagged_groups: MySettingNav.GroupsNotification('Your group notifications'),
      flagged_event: MySettingNav.InterestNotification('Your event notifications'),
      flagged_comments: MySettingNav.CommentsNotification('Your comments notifications'),
    }
  );


export default {
  title: 'Bundles / Member / My settings',
};
