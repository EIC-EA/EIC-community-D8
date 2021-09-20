export default function getTranslation(key, namespace = null) {
  if (!namespace) {
    return window.drupalSettings.translations[key];
  }

  return window.drupalSettings.translations[namespace][key];
}
