import axios from 'axios'

const config = window.__UI_MANAGER_CONFIG__ || {}

export const api = axios.create({
  baseURL: config.apiBase || '/ui-manager/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
})

// Attach CSRF token for Laravel
api.interceptors.request.use((config) => {
  const token = document.querySelector('meta[name="csrf-token"]')?.content
  if (token) config.headers['X-CSRF-TOKEN'] = token
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    const msg = err.response?.data?.message || err.message
    return Promise.reject(new Error(msg))
  }
)

export function useApi() {
  return { api }
}
