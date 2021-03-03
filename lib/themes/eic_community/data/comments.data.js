import author from '@theme/data/author.data';

export default {
  items: [
    {
      comment: 'Initial comment #1',
      comment_id: 'comment-001',
      author: author,
      is_owner: true,
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
          items: [
            {
              author: author,
              comment_id: 'comment-121',
              comment: 'Reply on reply #2',
              timestamp: 'Today at 13:56',
            },
          ],
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
