export default {
  items: [
    {
      comment: 'Initial comment #1',
      items: [
        { comment: 'Reply #1 from #1' },
        { comment: 'Reply #2 from #1', items: [{ comment: 'Reply on reply #2' }] },
      ],
    },
    {
      comment: 'Initial comment #2',
      items: [{ comment: 'Reply #1 from #2' }],
    },
  ],
};
