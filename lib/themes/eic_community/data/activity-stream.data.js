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
                    value: 4,
                    icon: {
                        name: 'comment',
                        type: 'custom',
                    },
                },
            ],
            type: {
                icon: {
                    name: 'question',
                    type: 'custom',
                },
                label: 'Question',
            },
        },
    ]
};