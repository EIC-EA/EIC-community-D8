export default {
  fields: [
    {
      title: 'Account information',
      fields: [
        {
          label: 'Email address',
          invalid_text:
            "Invalid email address. Valid e-mail can contain only latin letters, numbers, '@' and '.'",
          helper_text: 'This address will be used for contact purpose',
          id: 'input-email',
          name: 'email',
        },
      ],
    },
    {
      title: 'Let us help',
      fields: [
        {
          label: 'I have feedback regarding...',
          multiple: true,
          type: 'select',
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
          helper_text: 'Please select one or more categories.',
          id: 'feedback-category',
          name: 'feedback-category',
        },
        {
          type: 'radio',
          label: 'Do you need help?',
          helper_id: 'radio-default-helper',
          helper_text: 'Do we need to contact you personally?',
          name: 'radio-binary',
          invalid: false,
          binary: true,
          items: [
            {
              id: 'radio-default-1',
              value: 'yes',
              label: 'Yes',
            },
            {
              id: 'radio-default-2',
              value: 'no',
              label: 'No',
            },
          ],
        },
        {
          type: 'textarea',
          label: 'Comment',
          placeholder: 'Please enter your comment',
          helper_text: 'Your comment may be 255 characters long maximum',
          id: 'input-comment',
          name: 'comment',
          rows: 4,
        },
        {
          type: 'checkbox',
          id: 'copy',
          name: 'copy',
          helper_text: 'We send you a copy of the filled form.',
          items: [
            {
              id: 'send-copy',
              value: 'true',
              label: 'Send me a copy',
            },
          ],
        },
        {
          type: 'submit',
          label: 'Send feedback',
        },
      ],
    },
  ],
};
