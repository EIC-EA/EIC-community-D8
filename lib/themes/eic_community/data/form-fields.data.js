export default {
  fields: [
    {
      title: 'Basic information',
      fields: [
        {
          type: 'text',
          label: 'Firstname',
          id: 'input-firstname',
          name: 'firstname',
          extra_attributes: [
            { name: 'readonly' },
            {
              name: 'value',
              value: 'John',
            },
          ],
        },
        {
          type: 'text',
          label: 'Lastname',
          id: 'input-lastname',
          name: 'lastname',
          extra_attributes: [
            { name: 'readonly' },
            {
              name: 'value',
              value: 'Doe',
            },
          ],
        },
        {
          type: 'text',
          label: 'Email',
          id: 'input-email',
          name: 'email',
          extra_attributes: [
            { name: 'readonly' },
            {
              name: 'value',
              value: 'johndoe@example.com',
            },
          ],
        },
        {
          type: 'text',
          label: 'Subject',
          id: 'input-subject',
          name: 'subject',
          invalid: true,
          required: true,
          required_text: '*',
          invalid_text: 'Fields with * are mandatory.',
        },
        {
          label: 'Category',
          type: 'select',
          options: [
            {
              value: 1,
              label: '- Any -',
            },
          ],
          invalid: false,
          invalid_text: 'Error message',
          helper_text: 'Help message',
          disabled: false,
          id: 'input-category',
          name: 'input-category',
          width: 'full',
        },
        {
          label: 'Message',
          type: 'textarea',
          id: 'input-comment',
          name: 'comment',
          rows: 4,
        },
        {
          type: 'checkbox',
          invalid_text: 'Error message for the group',
          name: 'send-copy',
          invalid: false,
          required: false,
          items: [
            {
              id: 'send-copy',
              value: 'true',
              label: 'Send yourself a copy',
            },
          ],
        },
        {
          type: 'submit',
          label: 'Send<br/>message',
        },
      ],
    },
  ],
};
