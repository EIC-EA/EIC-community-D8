function url(path) {
  const currentPath = window.drupalSettings.path
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
