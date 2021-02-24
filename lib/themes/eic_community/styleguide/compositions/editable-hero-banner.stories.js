import docs from './editable-hero-banner.docs.mdx';

import EditableHeroBanner from '@theme/patterns/compositions/editable-hero-banner.html.twig';
import HeaderExampleTemplate from '@theme/snippets/header-example.html.twig';

export const Base = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
  });

export const Image = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
    image: 'https://picsum.photos/1600/450',
  });

export const Wide = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide',
    content: HeaderExampleTemplate(),
  });

export const WideImage = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide',
    content: HeaderExampleTemplate(),
    image: 'https://picsum.photos/1600/450',
  });

export const Blue = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-blue',
    content: HeaderExampleTemplate(),
  });

export const BlueImage = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-blue',
    content: HeaderExampleTemplate(),
    image: 'https://picsum.photos/1600/450',
  });

export const WideBlue = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-blue',
    content: HeaderExampleTemplate(),
  });

export const WideBlueImage = () =>
  EditableHeroBanner({
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-blue',
    content: HeaderExampleTemplate(),
    image: 'https://picsum.photos/1600/450',
  });

export const Grey = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
    extra_classes: 'ecl-editable-hero-banner--is-grey',
  });

export const GreyImage = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
    extra_classes: 'ecl-editable-hero-banner--is-grey',
    image: 'https://picsum.photos/1600/450',
  });

export const WideGrey = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
    extra_classes: 'ecl-editable-hero-banner--is-wide ecl-editable-hero-banner--is-grey',
  });

export const WideGreyImage = () =>
  EditableHeroBanner({
    content: HeaderExampleTemplate(),
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
