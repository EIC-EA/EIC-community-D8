import docs from './bulletin-block.docs.mdx';

import BulletinBlockTemplate from '@theme/patterns/compositions/bulletin-block.html.twig';
import bulletinBlock from '@theme/data/bulletin-block.data.js';

export const Base = () => BulletinBlockTemplate(bulletinBlock);

export const List = () =>
  BulletinBlockTemplate(
    Object.assign(
      {
        extra_classes: 'ecl-bulletin-block--as-list',
      },
      bulletinBlock
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
