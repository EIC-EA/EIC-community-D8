import FilelistTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';

import teaser from '@theme/data/teaser';

export const FilelistTeaser = () => FilelistTemplate(teaser.filelist);

export default {
  title: 'Compositions / Gallery',
  /* parameters: {
    docs: {
      page: docs,
    },
  }, */
};
