import React from 'react';
const svg = require('../../../../svg/svg')
import getTranslation from "../../../../Services/Translations";

const TimeStatus = (props) => {
  return <time className="ecl-timestamp__label">{props.comment.archived_flag_time || props.comment.deleted_flag_time || props.comment.edited_time || props.comment.created_time}</time>
}

export default TimeStatus;
