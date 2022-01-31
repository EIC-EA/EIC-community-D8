import React from 'react';

const TimeStatus = (props) => {
  return <time className="ecl-timestamp__label">{props.comment.archived_flag_time || props.comment.deleted_flag_time || props.comment.soft_deleted_time || props.comment.edited_time || props.comment.created_time}</time>
}

export default TimeStatus;
