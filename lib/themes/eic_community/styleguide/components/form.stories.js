import docs from './form.docs.mdx';

import fromData from '../../data/form';

import TextareaTemplate from '@ecl-twig/ec-component-text-area/ecl-text-area.html.twig';
import CheckboxTemplate from '@theme/patterns/components/form/form-checkbox.html.twig';
import RadioTemplate from '@theme/patterns/components/form/form-radio.html.twig';
import SelectTemplate from '@ecl-twig/ec-component-select/ecl-select.html.twig';
import FileTemplate from '@theme/patterns/components/form/form-file.html.twig';
import InputTemplate from '@ecl-twig/ec-component-text-input/ecl-text-input.html.twig';
import RadioBlockTemplate from '@theme/patterns/components/form/form-radio-block.html.twig';



export const Checkbox = () =>
  CheckboxTemplate(fromData.componentCheckbox);

export const Files = () =>
  FileTemplate(fromData.componentFiles);

export const Input = () =>
  InputTemplate(fromData.componentInput);

export const Radio = () =>
  RadioTemplate(fromData.componentRadio);

export const RadioBlock = () =>
  '<div class="ecl-form">'+RadioBlockTemplate(fromData.componentRadioBlock)+'</div>';

export const Select = () =>
  SelectTemplate(fromData.componentSelect);

export const Textarea = () =>
  TextareaTemplate(fromData.componentTextarea);

export default {
  title: 'Components / Form',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
