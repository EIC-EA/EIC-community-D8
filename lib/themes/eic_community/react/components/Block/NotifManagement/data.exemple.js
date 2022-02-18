export default {
  title: 'Topics',
  items:[
    {
      id: 1,
      name: {
        label: 'Topic 01',
        path: '#'
      },
      state: true,
      childCount: 2,
      items:[
        {
          id:11,
          name: {
            label: 'Topic 01: subtopic 01',
            path: '#'
          },
          state: false
        },
        {
          id:12,
          name: {
            label: 'Topic 01: subtopic 02',
            path: '#'
          },
          state: true
        }
      ]
    },
    {
      id: 2,
      name: {
        label: 'Topic 02',
        path: '#'
      },
      state: false,
    },
    {
      id: 3,
      name: {
        label: 'Test topic',
        path: '#'
      },
      state: false,
    }
  ],
}
