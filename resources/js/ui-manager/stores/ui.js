import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '../composables/useApi.js'

export const useUiStore = defineStore('ui', () => {
  const pages = ref([])
  const variables = ref([])
  const loading = ref(false)
  const error = ref(null)

  const pageMap = computed(() => {
    const map = {}
    pages.value.forEach(p => { map[p.name] = p })
    return map
  })

  async function fetchPages() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/pages')
      pages.value = data.data
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  async function fetchVariables() {
    const { data } = await api.get('/variables')
    variables.value = data.data
  }

  async function saveSectionFields(page, section, fields) {
    const { data } = await api.put(`/pages/${page}/sections/${section}`, { fields })
    return data.data
  }

  async function fetchSection(page, section) {
    const { data } = await api.get(`/pages/${page}/sections/${section}`)
    return data.data
  }

  // Repeatable operations
  async function addItem(page, section, fields) {
    const { data } = await api.post(`/pages/${page}/sections/${section}/items`, { fields })
    return data.data
  }

  async function updateItem(page, section, itemId, fields) {
    const { data } = await api.put(`/pages/${page}/sections/${section}/items/${itemId}`, { fields })
    return data.data
  }

  async function deleteItem(page, section, itemId) {
    await api.delete(`/pages/${page}/sections/${section}/items/${itemId}`)
  }

  async function reorderItems(page, section, order) {
    await api.post(`/pages/${page}/sections/${section}/reorder`, { order })
  }

  return {
    pages,
    variables,
    loading,
    error,
    pageMap,
    fetchPages,
    fetchVariables,
    saveSectionFields,
    fetchSection,
    addItem,
    updateItem,
    deleteItem,
    reorderItems,
  }
})
