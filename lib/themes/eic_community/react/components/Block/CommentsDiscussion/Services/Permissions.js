function hasPermission(permissionId) {
  return window.drupalSettings.overview.permissions[permissionId];
}

function canShowActions(comment) {
  const isOwnerComment = parseInt(comment.user_id) === parseInt(window.drupalSettings.user.uid);

  return (hasPermission('edit_own_comments') && isOwnerComment) ||
    hasPermission('edit_all_comments') ||
    (hasPermission('can_request_delete') && !isOwnerComment) ||
    (hasPermission('can_request_archive') && !isOwnerComment) ||
    (hasPermission('delete_all_comments') || isOwnerComment);
}

export {hasPermission, canShowActions}
