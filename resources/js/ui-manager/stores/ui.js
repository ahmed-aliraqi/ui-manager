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

  function layoutParam(layout) {
    return layout && layout !== 'default' ? `?layout=${layout}` : ''
  }

  async function saveSectionFields(page, section, fields, layout = null) {
    const { data } = await api.put(`/pages/${page}/sections/${section}${layoutParam(layout)}`, { fields })
    return data.data
  }

  async function fetchSection(page, section, layout = null) {
    const { data } = await api.get(`/pages/${page}/sections/${section}${layoutParam(layout)}`)
    return data.data
  }

  // Repeatable operations
  async function addItem(page, section, fields, layout = null) {
    const { data } = await api.post(`/pages/${page}/sections/${section}/items${layoutParam(layout)}`, { fields })
    return data.data
  }

  async function updateItem(page, section, itemId, fields, layout = null) {
    const { data } = await api.put(`/pages/${page}/sections/${section}/items/${itemId}${layoutParam(layout)}`, { fields })
    return data.data
  }

  async function deleteItem(page, section, itemId, layout = null) {
    await api.delete(`/pages/${page}/sections/${section}/items/${itemId}${layoutParam(layout)}`)
  }

  async function reorderItems(page, section, order, layout = null) {
    await api.post(`/pages/${page}/sections/${section}/reorder${layoutParam(layout)}`, { order })
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
