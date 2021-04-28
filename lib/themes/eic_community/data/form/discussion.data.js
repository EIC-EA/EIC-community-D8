export default {
  fields: [
    {
      title: 'Add a new Discussion',
      fields: [
        {
          type: 'radio',
          label: 'I want to start',
          helper_id: 'radio-default-helper',
          name: 'radio-binary',
          invalid: false,
          binary: true,
          items: [
            {
              id: 'type-1',
              value: 'idea',
              label: 'Idea',
              icon: {
                name: 'idea',
                type: 'custom',
              },
            },
            {
              id: 'type-2',
              value: 'question',
              label: 'Question',
              icon: {
                name: 'question',
                type: 'custom',
              },
            },
            {
              id: 'type-3',
              value: 'discussion',
              label: 'Discussion',
              icon: {
                name: 'discussion',
                type: 'custom',
              },
            },
          ],
        },
        {
          label: 'Idea Title',
          invalid_text: false,
          id: 'title',
          name: 'text',
        },
        {
          label: 'Give people more detail about your idea',
          required: true,
          required_text: '*',
          helper_text: 'Your description can contain up to 300 characters.',
          type: 'textarea',
        },
        {
          id: 'attachments',
          label: 'Attachments',
          type: 'file',
          helper_text:
            'Adding attachments related to the feedback will help us out. <br/>Only txt doc docx pdf odt rtf files. Maximum size is 5 MB. Encrypted documents and those containing macros are not accepted.',
          disabled: false,
          required: false,
          invalid: false,
          multiple: true,
          button_choose_label: 'Choose file',
          button_replace_label: 'Replace file',
        },
        {
          label: 'Horizontal Topics',
          type: 'select',
          options: [
            {
              value: -1,
              label: '- Select a topic -',
            },
            {
              value: 1,
              label: 'Accessibility',
            },
            {
              value: 2,
              label: 'Privacy',
            },
            {
              value: 3,
              label: 'Press related',
            },
            {
              value: 4,
              label: 'General Help',
            },
            {
              value: 4,
              label: 'Website related feedback',
            },
          ],
          helper_text: 'Please select one or more categories.',
          id: 'feedback-category',
          name: 'feedback-category',
        },
        {
          label: 'Themes',
          type: 'select',
          multiple: true,
          required: true,
          required_text: '*',
          multiple_placeholder: '- Select a theme -',
          options: [
            {
              value: 2,
              label: 'Privacy',
            },
            {
              value: 3,
              label: 'Press related',
            },
            {
              value: 4,
              label: 'General Help',
            },
            {
              value: 4,
              label: 'Website related feedback',
            },
          ],
          helper_text: 'Please select one or more categories.',
          id: 'feedback-category',
          name: 'feedback-category',
        },
        {
          label: 'Regions and Countries',
          type: 'select',
          multiple: true,
          multiple_placeholder: '- Select regions and or countries -',
          options: [
            {
              value: 1,
              label: 'Accessibility',
            },
            {
              value: 2,
              label: 'Privacy',
            },
            {
              value: 3,
              label: 'Press related',
            },
            {
              value: 4,
              label: 'General Help',
            },
            {
              value: 4,
              label: 'Website related feedback',
            },
          ],
          helper_text: 'You can choose up to 3 regions and unlimited countries.',
          id: 'feedback-category',
          name: 'feedback-category',
        },
        {
          type: 'text',
          label: 'add additional tags (optional)',
          helper_text: 'Type your tag. Add multiple tags with a comma.',
        },
      ],
    },
    {
      extra_classes: 'ecl-form__section--has-toolbar-layout',
      fields: [
        {
          type: 'submit',
          label: 'Start idea',
        },
        {
          type: 'button',
          label: 'Save as draft',
        },
        {
          link: {
            label: 'Cancel',
            path: '#',
          },
        },
      ],
    },
  ],
};
