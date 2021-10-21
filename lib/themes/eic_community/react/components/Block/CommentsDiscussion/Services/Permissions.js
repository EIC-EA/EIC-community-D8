import axios from 'axios';
import {getPrefixDiscussionEndpoint} from "./UrlHelper";

function hasPermission(permissionId) {
  return window.drupalSettings.overview.permissions[permissionId];
}

function canShowActions(comment) {
  const isOwnerComment = parseInt(comment.user_id) === parseInt(window.drupalSettings.user.uid);

  return (hasPermission('edit_own_comments') && isOwnerComment) ||
    hasPermission('edit_all_comments') ||
    hasPermission('delete_all_comments');
}

function hasPermissionApi(comment, flag) {
  return axios.get(`${getPrefixDiscussionEndpoint()}/0/has-flag-permission/${comment.comment_id}/${flag}`,
    {withCredentials: true})
    .then(function (response) {
      return response.data.allowed;
    });
}

export {hasPermission, canShowActions, hasPermissionApi}
