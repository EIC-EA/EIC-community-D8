import common from '@theme/data/common.data';

export default {
  title: 'File lorem ipsum',
  body:  '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Eu scelerisque felis imperdiet proin fermentum leo vel orci. Amet justo donec enim diam vulputate. Malesuada bibendum arcu vitae elementum curabitur. Sit amet nisl suscipit adipiscing bibendum est ultricies. Cursus eget nunc scelerisque viverra mauris in aliquam sem. Diam sit amet nisl suscipit adipiscing. Nunc sed velit dignissim sodales ut eu sem integer.</<p>Velit euismod in pellentesque massa placerat duis ultricies lacus sed. Vel orci porta non pulvinar neque. Ornare aenean euismod elementum nisi quis eleifend quam adipiscing.</p>',
  icon_file_path: common.icon_file_path,
  download: 'Download',
  files: [
    {
      name: 'fFilename.doc',
      path: '#file1',
      type: 'File',
      mime_type: 'doc',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'xFilename.dwg',
      path: '#file2',
      type: 'File',
      mime_type: 'dwg',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'qFilename.html',
      path: '#',
      type: 'File',
      mime_type: 'html',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'aFilename.ppt',
      path: '#',
      type: 'File',
      mime_type: 'ppt',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'jFilename.text',
      path: '#',
      type: 'File',
      mime_type: 'txt',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'aaFilename.xls',
      path: '#',
      type: 'File',
      mime_type: 'xls',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    },
    {
      name: 'mFilename.zip',
      path: '#',
      type: 'File',
      mime_type: 'zip',
      stats: [
        {
          label: 'Downloads',
          value: 8,
          icon: {
            type: 'custom',
            name: 'download',
          },
        },
        {
          value: '1.5 MB',
          label: 'Size',
          hide_label: true
        }
      ]
    }
  ]
}
