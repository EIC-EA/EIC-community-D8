import FilelistTemplate from '@theme/patterns/compositions/filelist/filelist.teaser.html.twig';
import FilelistGalleryTemplate from '@theme/patterns/compositions/filelist/filelist.gallery.html.twig';

import teaser from '@theme/data/teaser';
import files from "@theme/data/gallery.data"

export const FilelistTeaser = () => FilelistTemplate(teaser.filelist);
export const FilelistGallery = () => FilelistGalleryTemplate(files);

export default {
  title: 'Compositions / Gallery',
  /* parameters: {
    docs: {
      page: docs,
    },
  }, */
};
