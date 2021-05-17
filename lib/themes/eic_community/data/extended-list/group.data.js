import { editableField } from '@theme/snippets';

export default {
  title: 'Group Details',
  items: [
    {
      title: 'Group owner',
      items: [
        {
          title: 'Jane Doe',
          path: '?user=group-owner--jane-doe',
        },
      ],
    },
    {
      title: 'Administrator',
      group: true,
      items: [
        {
          title: 'Jane Doe',
          path: '?user=group-owner--jane-doe',
        },
        {
          title: 'Claudia Kyle',
          path: '?user=group-owner--claudia-kyle',
        },
      ],
    },
    {
      title: 'Group Topics',
      items: [
        {
          title: 'Thematic',
          items: [
            {
              title: 'Energy',
            },
            {
              title: 'Engineering & Technology',
            },
            {
              title: 'Public Section innovation',
            },
          ],
        },
        {
          title: 'Horizontal',
          items: [
            {
              title: 'Marketing',
              items: [
                {
                  title: 'Branding',
                },
                {
                  title: 'Communication',
                },
              ],
            },
            {
              title: 'Skill Development',
            },
          ],
        },
      ],
    },
    {
      title: 'Group region and countries',
      items: [
        {
          title: 'Europe',
          items: [
            {
              title: 'France',
            },
            {
              title: 'Belgium',
            },
            {
              title: 'Germany',
            },
          ],
        },
      ],
    },
    {
      title: 'Group description',
      content: editableField(),
    },
  ],
};
