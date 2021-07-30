const mix = require('laravel-mix');
require('laravel-mix-svg');

// Public path is where the files will be created
mix.setPublicPath(`dist/react`);

mix.js(`react/components/Block/Overview/entrypoint.js`, `dist/react/block/overview`)

mix.svg({
  class: 'ecl-icon',
  assets: ['./images/sprite/custom/'], // a list of directories to search svg images
  output: './react/svg/svg.js', // Where the craeted js file needs to go.
})
