
export function addParamsToUrl(key, value) {
  const url = new URL(window.location.href)
  const search_params = url.searchParams

  search_params.set(key, value.toString())
  url.search = search_params.toString()

  window.history.pushState({}, '', url.toString());
}

export function getParamsFromUrl(key) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(key.toString());
}
