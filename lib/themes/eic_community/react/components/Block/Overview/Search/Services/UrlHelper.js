function getLikeContentStatusEndpoint(groupId, nodeId) {
    return `${window.drupalSettings.path.baseUrl}group/${groupId}/like-status/${nodeId}`;
}

function getLikeContentEndpoint(groupId, nodeId, action) {
    return `${window.drupalSettings.path.baseUrl}group/${groupId}/like-content/${action}/${nodeId}`;
}

export {getLikeContentStatusEndpoint, getLikeContentEndpoint}
