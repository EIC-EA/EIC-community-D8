export default {
  title: 'Topics',
  items:[
    {
      name: {
        label: 'Topic 01',
        path: '#'
      },
      state: true,
      items:[
        {
          name: {
            label: 'Topic 01: subtopic 01',
            path: '#'
          },
          state: false
        },
        {
          name: {
            label: 'Topic 01: subtopic 02',
            path: '#'
          },
          state: true
        }
      ]
    },
    {
      name: {
        label: 'Topic 02',
        path: '#'
      },
      state: false,
    },
    {
      name: {
        label: 'Test topic',
        path: '#'
      },
      state: false,
    }
  ],
}
