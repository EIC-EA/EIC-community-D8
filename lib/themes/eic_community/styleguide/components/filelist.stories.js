import docs from './filelist.docs.mdx';

import FilelistTemplate from '@theme/patterns/components/filelist.html.twig';

import filelist from '@theme/data/filelist.data';


export const Base = () => FilelistTemplate(filelist);


export default {
  title: 'Components / Filelist',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
