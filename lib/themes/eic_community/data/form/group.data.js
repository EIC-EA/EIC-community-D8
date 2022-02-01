import { editableField } from '@theme/snippets';

export default {
  description: editableField(
    'Start a new group with community members'
  ),
  fields: [
    {
      fields: [
        {
          type: 'text',
          label: 'Group name',
          id: 'input-groupname',
          name: 'groupname',
          helper_text: 'Number of characters: 300'
        },
        {
          type: 'text',
          label: 'group welcome Message',
          id: 'input-welcome',
          name: 'welcome',
        },

        {
          label: 'description of your group',
          type: 'textarea',
          id: 'input-description',
          name: 'description',
          rows: 4,
          helper_text: 'Number of characters: 300'
        },


        {
          type: 'radio-block',
          label: 'group visibility & access',
          name: 'radio-block-visibility',
          invalid: false,
          items: [
            {
              id: 'visibility-1',
              value: 'Public',
              label: 'Public',
              text: 'This means the restricted group will be visible to every user on the platform.',
              icon: {
                name: 'group',
                type: 'custom',
              },
            },
            {
              id: 'visibility-2',
              value: 'Community members only',
              label: 'Community members only',
              text: 'This means the restricted group will be visible to each trusted user on the platform. ',
              icon: {
                name: 'lock',
                type: 'custom',
              },
              field: {
                type: 'radio',
                name: 'radio-subfield',
                items: [
                  {
                    id: 'subtype-1',
                    value: 'Certain organisation types',
                    label: 'Certain organisation types'
                  },
                  {
                    id: 'subtype-2',
                    value: 'Specific organisations',
                    label: 'Specific organisations'
                  },
                  {
                    id: 'subtype-3',
                    value: 'Specific trusted users',
                    label: 'Specific trusted users'
                  },
                  {
                    id: 'subtype-4',
                    value: 'Specific email domains',
                    label: 'Specific email domains'
                  },
                ],
              }
            },
            {
              id: 'visibility-3',
              value: 'Custom restriction',
              label: 'Custom restriction',
              text: 'This means the restricted group will be visible to each of the trusted users that comply with any of the following restrictions:',
              icon: {
                name: 'lock',
                type: 'custom',
              },
            },
            {
              id: 'visibility-4',
              value: 'Private group',
              label: 'Private group',
              text: 'This means the group will only be visible to the group members. ',
              icon: {
                name: 'lock',
                type: 'custom',
              },
            }
          ],
        },

        {
          type: 'radio-block',
          label: 'membership request type',
          name: 'radio-block-membership',
          invalid: false,
          items: [
            {
              id: 'type-1',
              value: 'Open',
              label: 'Open',
              text: 'Community members can join immediately.'
            },
            {
              id: 'type-2',
              value: 'Moderated',
              label: 'Moderated',
              text: 'Community members can request to join and this needs to be validated by the Group owner or administrator.'
            },
          ],
        },

        {
          label: 'topics',
          type: 'select',
          options: [
            {
              value: -1,
              label: '- Select a topic -',
            },
            {
              value: 2,
              label: '- Any -',
            },
          ],
          invalid: false,
          invalid_text: 'Error message',
          helper_text: 'You can select up to three main topics and unlimited sub-topics',
          disabled: false,
          id: 'input-topics',
          name: 'input-topics',
          width: 'full',
        },

        {
          label: 'region or country',
          type: 'select',
          options: [
            {
              value: -1,
              label: '- Select a country -',
            },
            {
              value: 2,
              label: '- Any -',
            },
          ],
          invalid: false,
          invalid_text: 'Error message',
          helper_text: 'You can select up to three regions and unlimited countries',
          disabled: false,
          id: 'input-country',
          name: 'input-country',
          width: 'full',
        },

        {
          id: 'attachments',
          label: 'add an image',
          type: 'file',
          helper_text:
            'Select .jpg or .png files only',
          disabled: false,
          required: false,
          invalid: false,
          multiple: false,
          button_choose_label: 'Upload an image',
          button_replace_label: 'Replace image',
        },

        {
          label: 'message to the administrators (optional)',
          type: 'textarea',
          id: 'input-message',
          name: 'message',
          rows: 4,
        },

        {
          text: 'Your request will be checked by the community managers. We will notify you when it has been approved.',
          type: 'info',
        },


        {
          type: 'submit',
          label: 'Request new group',
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
