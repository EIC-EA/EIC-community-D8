import docs from './editable-hero-banner.docs.mdx';

import editableHeroBanner from '@theme/patterns/compositions/editable-hero-banner.html.twig';

import { editableField } from '@theme/snippets';

export const Base = () =>
  editableHeroBanner({
    content: editableField(),
  });

export const Image = () =>
  editableHeroBanner({
    content: editableField(),
    image: 'https://picsum.photos/1600/450',
  });

export const Wide = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide',
    content: editableField(),
  });

export const WideImage = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide',
    content: editableField(),
    image: 'https://picsum.photos/1600/450',
  });

export const Blue = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-blue',
    content: editableField(),
  });

export const BlueImage = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-blue',
    content: editableField(),
    image: 'https://picsum.photos/1600/450',
  });

export const WideBlue = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-blue',
    content: editableField(),
  });

export const WideBlueImage = () =>
  editableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-blue',
    content: editableField(),
    image: 'https://picsum.photos/1600/450',
  });

export const Grey = () =>
  editableHeroBanner({
    content: editableField(),
    extra_classes: 'ecl-editable-hero-banner--is-grey',
  });

export const GreyImage = () =>
  editableHeroBanner({
    content: editableField(),
    extra_classes: 'ecl-editable-hero-banner--is-grey',
    image: 'https://picsum.photos/1600/450',
  });

export const WideGrey = () =>
  editableHeroBanner({
    content: editableField(),
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-grey',
  });

export const WideGreyImage = () =>
  editableHeroBanner({
    content: editableField(),
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-grey',
    image: 'https://picsum.photos/1600/450',
  });

export default {
  title: 'Compositions / Editable Hero Banner',
  parameters: {
    docs: {
      page: docs,
    },
  },
};
