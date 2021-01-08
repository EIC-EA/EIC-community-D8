/**
 * Defines the Storybook Pages that should contain all of the theme templates.
 */
export default {
  title: "Pages"
}

import contentPageRender from '@theme/pages/content-page.html.twig';
import { site_header } from '~/data/common.data.js';

export const contentPage = () => contentPageRender({ site_header })
