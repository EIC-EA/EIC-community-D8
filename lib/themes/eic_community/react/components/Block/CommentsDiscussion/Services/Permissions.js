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

function isCommentHidden(comment) {
  return comment.archived_flag_time !== null || comment.deleted_flag_time !== null || parseInt(comment.is_soft_delete) !== 0 || parseInt(comment.is_archived) !== 0;
}

function hasPermissionApi(comment, flag) {
  return axios.get(`${getPrefixDiscussionEndpoint()}/0/has-flag-permission/${comment.comment_id}/${flag}`,
    {withCredentials: true})
    .then(function (response) {
      return response.data.allowed;
    });
}

export {hasPermission, canShowActions, isCommentHidden, hasPermissionApi}
