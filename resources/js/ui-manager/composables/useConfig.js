/**
 * Access the UI Manager configuration injected by the PHP backend
 * via window.__UI_MANAGER_CONFIG__ in the Blade template.
 */
export function useConfig() {
  return window.__UI_MANAGER_CONFIG__ ?? {}
}

export function useLocales() {
  const config = useConfig()
  return {
    locales: config.locales ?? ['en'],
    defaultLocale: config.defaultLocale ?? 'en',
  }
}
