import authorTemplate from '@theme/patterns/components/author.html.twig';
import readTimeTemplate from '@theme/patterns/components/read-time.html.twig';

export default {
  image: {
    src: 'http://placehold.it/800x250',
    alt: 'Culpa minim culpa quis tempor id pariatur exercitation ea culpa nisi tempor mollit.',
  },
  items: [
    {
      content: authorTemplate({
        author: 'John Doe',
      }),
    },
    {
      content: readTimeTemplate({
        label: '8 minutes read',
        icon_file_path: 'images/icons/sprites/icons.svg',
      }),
    },
  ],
};
