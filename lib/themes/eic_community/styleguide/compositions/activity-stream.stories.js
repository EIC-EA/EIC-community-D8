import docs from './activity-stream.docs.mdx';

import ActivityStreamTemplate from '@theme/patterns/compositions/activity-stream.html.twig';

import activityStream from '@theme/data/activity-stream.data.js';

export const Base = () => ActivityStreamTemplate(activityStream);

export default {
  title: 'Compositions / Activity Stream',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
