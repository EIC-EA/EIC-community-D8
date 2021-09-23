export default function getTranslation(key, namespace = null) {
  if (!namespace) {
    return window.drupalSettings.overview.translations[key];
  }

  return window.drupalSettings.overview.translations[namespace][key];
}
