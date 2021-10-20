function getPrefixDiscussionEndpoint() {
  if (window.location.pathname.indexOf("/community/") === 0) {
    return '/community/api/discussion';
  }

  return '/api/discussion';
}

export {getPrefixDiscussionEndpoint}
