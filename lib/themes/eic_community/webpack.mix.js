const mix = require('laravel-mix');
require('laravel-mix-svg');

// Public path is where the files will be created
mix.setPublicPath(`dist/react`);
mix.options({ runtimeChunkPath: 'custom' });
mix
  .js(`react/components/Block/Overview/entrypoint.js`, `dist/react/block/overview`)
  .js(`react/components/Field/EntityTree/entrypoint.js`, `dist/react/field/entity_tree`)
  .js(`react/components/Block/ActivityStream/entrypoint.js`, `dist/react/block/activity_stream`)
  .js(
    `react/components/Block/CommentsDiscussion/entrypoint.js`,
    `dist/react/block/comments_discussion`
  )
  .js(`react/components/Block/ShareModal/entrypoint.js`, `dist/react/block/share_modal`)
  .js(`react/components/Block/Gallery/entrypoint.js`, `dist/react/block/gallery`)
  .js(`react/components/Block/Announcements/entrypoint.js`, `dist/react/block/announcements`)
  .js(`react/components/Block/NotifManagement/entrypoint.js`, `dist/react/block/notifmanagement`)
  .js(`react/components/Block/Toggle/entrypoint.js`, `dist/react/block/toggle`)
  .js(`react/components/Block/StreamVideo/entrypoint.js`, `dist/react/block/streamvideo`)
  .extract(
    ['react', 'react-dom', 'react-router-dom', 'axios', '@material-ui', 'lodash-es', 'scheduler'],
    'dist/react/vendor.js'
  );

mix.svg({
  class: 'ecl-icon',
  assets: ['./images/sprite/custom/'], // a list of directories to search svg images
  output: './react/svg/svg.js', // Where the craeted js file needs to go.
});
