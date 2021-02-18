import docs from './bulletin-block.docs.mdx';

import BulletinBlockTemplate from '@theme/patterns/compositions/bulletin-block.html.twig';
import BulletinBlock from '@theme/data/bulletin-block.data.js';

export const Base = () => BulletinBlockTemplate(BulletinBlock);

export const List = () =>
  BulletinBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-bulletin-block--as-list',
      },
      BulletinBlock
    )
  );

export default {
  title: 'Compositions / Bulletin Block',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
