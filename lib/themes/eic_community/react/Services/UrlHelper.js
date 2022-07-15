function url(path) {
  const currentPath = window.drupalSettings.path

  if (!path) {
    return currentPath.baseUrl;
  }

  if (!currentPath.baseUrl.replace(/^\/+/g, '')) {
    return path
  }

  const regex = new RegExp(`^${currentPath.baseUrl}`, 'g')
  if (regex.exec(path)) {
    return path
  }

  return `${currentPath.baseUrl}${path.replace(/^\/+/g, '')}`
}

export {url}
