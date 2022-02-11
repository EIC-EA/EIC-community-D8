const mix = require('laravel-mix');
require('laravel-mix-svg');

// Public path is where the files will be created
mix.setPublicPath(`dist/react`);

mix.js(`react/components/Block/Overview/entrypoint.js`, `dist/react/block/overview`)
mix.js(`react/components/Field/EntityTree/entrypoint.js`, `dist/react/field/entity_tree`)
mix.js(`react/components/Block/ActivityStream/entrypoint.js`, `dist/react/block/activity_stream`)
mix.js(`react/components/Block/CommentsDiscussion/entrypoint.js`, `dist/react/block/comments_discussion`)
mix.js(`react/components/Block/ShareModal/entrypoint.js`, `dist/react/block/share_modal`)
mix.js(`react/components/Block/Gallery/entrypoint.js`, `dist/react/block/gallery`)
mix.js(`react/components/Block/Announcements/entrypoint.js`, `dist/react/block/announcements`)
mix.js(`react/components/Block/NotifManagement/entrypoint.js`, `dist/react/block/notifmanagement`)

mix.svg({
  class: 'ecl-icon',
  assets: ['./images/sprite/custom/'], // a list of directories to search svg images
  output: './react/svg/svg.js', // Where the craeted js file needs to go.
})
