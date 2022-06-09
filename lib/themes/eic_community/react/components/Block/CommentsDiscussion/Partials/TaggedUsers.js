import React from 'react';

const TaggedUsers = ({taggedUsers, generateTemporaryUrl = false}) => {
  if (taggedUsers.length === 0) {
    return <></>;
  }
  return <div>
    With: <span
    dangerouslySetInnerHTML={{__html: taggedUsers.map(user => `<a href="${generateTemporaryUrl ? '/user/' + user.tid : user.url}" class="ecl-comment__author-name">${user.name}</a>`).join(', ')}}
  />
  </div>
}

export default TaggedUsers;
