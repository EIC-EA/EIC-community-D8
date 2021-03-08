import docs from './investment.docs.mdx';

import InvestmentTemplate from '@theme/patterns/components/investment.html.twig';

import common from '@theme/data/common.data';

export const Base = () =>
  InvestmentTemplate({
    icon_file_path: common.icon_file_path,
    label: '&euro; 250.000.000',
    contributor: {
      label: 'Deutschland',
    },
  });

export const WithFlag = () =>
  InvestmentTemplate({
    icon_file_path: common.icon_file_path,
    label: '&euro; 250.000.000',
    contributor: {
      label: 'Deutschland',
      code: 'DE',
    },
  });

export default {
  title: 'Components / Investment',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
