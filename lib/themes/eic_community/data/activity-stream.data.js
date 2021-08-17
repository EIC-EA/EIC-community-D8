import common from '@theme/data/common.data';

export default {
    title: 'Latest member activity',
    icon_file_path: common.icon_file_path,
    items: [
        {
            description: description,
            image: {
                src: 'https://picsum.photos/144/144',
                alt: 'Avatar image of Jane Doe',
            },
            timestamp: {
                label: '16 hours ago',
            },
            stats: [
                {
                    label: 'Reactions',
                    value: 12,
                    icon: {
                        type: 'custom',
                        name: 'comment',
                    },
                },
                {
                    value: '27',
                    label: 'Views',
                    icon: {
                        type: 'custom',
                        name: 'views',
                    },
                },
                {
                    label: 'Likes',
                    value: 13,
                    icon: {
                        type: 'custom',
                        name: 'like',
                    },
                },
                {
                    label: 'Downloads',
                    value: 5,
                    icon: {
                      type: 'custom',
                      name: 'download',
                    },
                },
            ],
            type: {
                icon: {
                    name: 'document',
                    type: 'custom',
                },
                label: 'Document',
            },
        },
    ]
};