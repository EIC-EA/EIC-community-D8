import author from '@theme/data/author.data';

export default {
  title: 'Replies',
  items: [
    {
      comment: 'Initial comment #1',
      comment_id: 'comment-001',
      author: author,
      timestamp: '7 days ago',
      stats: [
        {
          label: 'Likes',
          value: 20,
          icon: {
            type: 'custom',
            name: 'like',
          },
        },
      ],
      items: [
        {
          author: author,
          comment_id: 'comment-011',
          comment: 'Reply #1 from #1',
          timestamp: '5 days ago',
        },
        {
          author: author,
          comment_id: 'comment-021',
          comment: 'Reply #2 from #1',
          timestamp: '6 days ago',
          is_owner: true,
          items: [
            {
              author: author,
              comment_id: 'comment-121',
              comment: 'Reply #1 on reply #2',
              timestamp: 'Today at 13:56',
              is_owner: true,
              attachments: [
                {
                  title:
                    'RG8gdm9sdXB0YXRlIExvcmVtIG51bGxhIHNpdCBldCBtaW5pbSBsYWJvcmlzIGlydXJlIGFsaXF1YSBwYXJpYXR1ciBpbi4=',
                  path: 'example.pdf',
                  icon: {
                    name: 'pdf',
                    type: 'custom',
                  },
                },
                {
                  title: 'Photo example',
                  path: 'https://picsum.photos/1200/1400',
                  type: 'jpeg',
                  image: {
                    src: 'https://picsum.photos/1200/1400',
                  },
                },
                {
                  title: 'Photo example',
                  path: 'https://picsum.photos/1200/1400',
                  type: 'jpeg',
                  image: {
                    src: 'https://picsum.photos/1200/1400',
                  },
                },
              ],
            },
            {
              author: author,
              comment_id: 'comment-011',
              comment: 'Reply #2 on reply #2',
              timestamp: '5 days ago',
            },
            {
              author: author,
              comment_id: 'comment-011',
              comment: 'Reply #3 on reply #2',
              timestamp: '5 days ago',
            },
          ],
        },
        {
          author: author,
          comment_id: 'comment-011',
          comment: 'Reply #3 from #1',
          timestamp: '5 days ago',
        },
        {
          author: author,
          comment_id: 'comment-011',
          comment: 'Reply #4 from #1',
          timestamp: '5 days ago',
        },
        {
          author: author,
          comment_id: 'comment-011',
          comment: 'Reply #5 from #1',
          timestamp: '5 days ago',
        },
      ],
    },
    {
      author: author,
      comment: 'Initial comment #2',
      comment_id: 'comment-002',
      timestamp: '12 days ago',
      items: [
        {
          author: author,
          timestamp: '3 days ago',
          comment_id: 'comment-012',
          comment: 'Reply #1 from #2',
        },
      ],
    },
  ],
};
