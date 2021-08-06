import FilelistTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';

import teaser from '@theme/data/teaser';

export const Teaser = () => FilelistTemplate(teaser.filelist);
export const TeaserHighlight = () => FilelistTemplate(
  Object.assign(
    {
      highlight: {
        is_active: true,
      },
    },
    teaser.filelist
  )
);

export default {
  title: 'Compositions / Filelist',
  /* parameters: {
    docs: {
      page: docs,
    },
  }, */
};
