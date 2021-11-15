

export default {
  field: {
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
}
