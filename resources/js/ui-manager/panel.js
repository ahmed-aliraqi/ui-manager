/**
 * UI Manager Panel — standalone entry point
 *
 * Include this bundle (+ its CSS) in any page that already has a Laravel
 * session. Then drop:
 *
 *   <div data-ui-manager-panel></div>
 *
 * anywhere in your HTML and the panel mounts itself automatically.
 *
 * Optional attributes:
 *   data-api-base="/ui-manager/api"    override the API base URL
 *   data-config='{"locales":["en","ar"],"defaultLocale":"en"}'
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import UiManagerPanel from './components/UiManagerPanel.vue'
import './assets/app.css'
import './bootstrap/scoped.scss'

function mount(el) {
  // Allow per-element config override via data-config attribute
  const dataConfig = el.dataset.config ? JSON.parse(el.dataset.config) : {}
  const apiBase    = el.dataset.apiBase ?? el.dataset.uiApiBase ?? null

  // Merge into the global config object (api composable reads it)
  window.__UI_MANAGER_CONFIG__ = {
    locales:       ['en'],
    defaultLocale: 'en',
    ...(window.__UI_MANAGER_CONFIG__ ?? {}),
    ...dataConfig,
    ...(apiBase ? { apiBase } : {}),
  }

  const app = createApp(UiManagerPanel)
  app.use(createPinia())
  app.mount(el)
}

// Auto-mount on all matching elements when DOM is ready
function init() {
  document.querySelectorAll('[data-ui-manager-panel]').forEach(mount)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init)
} else {
  init()
}

// Also expose for manual mounting
export { UiManagerPanel, mount }
