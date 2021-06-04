const mix = require('laravel-mix');

// Public path is where the files will be created
mix.setPublicPath(`dist/react`);

mix.js(`react/components/Paragraph/Banner/entrypoint.js`, `dist/react/paragraph/banner`)
mix.js(`react/components/Block/Group/Overview/entrypoint.js`, `dist/react/block/group/overview`)
