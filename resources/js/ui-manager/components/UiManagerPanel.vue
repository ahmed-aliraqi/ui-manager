<template>
  <BsWrapper class="uim-panel">

    <!-- ── Loading skeleton ─────────────────────────────────────────── -->
    <div v-if="store.loading">
      <div class="d-flex gap-2 mb-3">
        <div v-for="i in 3" :key="i" class="placeholder-glow">
          <span class="placeholder rounded" style="height:34px;width:90px;display:inline-block" />
        </div>
      </div>
      <div class="row g-3 align-items-stretch">
        <div class="col-md-3">
          <div class="card h-100" style="min-height:400px;border-radius:.5rem">
            <div class="card-body p-3">
              <div v-for="i in 5" :key="i" class="placeholder-glow mb-2">
                <span class="placeholder rounded w-100" style="height:36px;display:block" />
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card h-100" style="min-height:400px;border-radius:.5rem">
            <div class="card-body p-4">
              <div v-for="i in 4" :key="i" class="mb-4 placeholder-glow">
                <span class="placeholder col-3 d-block mb-1 rounded" style="height:14px" />
                <span class="placeholder col-12 d-block rounded" style="height:38px" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Main panel ────────────────────────────────────────────────── -->
    <template v-else-if="store.pages.length">

      <!-- Pages nav -->
      <div class="d-flex gap-1 flex-nowrap overflow-auto mb-3 pb-1">
        <button
          v-for="page in store.pages"
          :key="page.name"
          type="button"
          class="uim-panel__page-btn text-nowrap"
          :class="{ active: currentPage === page.name }"
          @click="selectPage(page.name)"
        >
          {{ page.display_name }}
        </button>
      </div>

      <!-- Two-card layout: sidebar + form -->
      <div class="row g-3 align-items-stretch">

        <!-- ── Sections sidebar card ──────────────────────────────── -->
        <div class="col-md-3">
          <div class="card h-100" style="min-height:400px;border-radius:.5rem">
            <div class="card-header py-2 px-3">
              <p class="mb-0 fw-semibold text-secondary text-uppercase" style="font-size:.7rem;letter-spacing:.06em">
                Sections
              </p>
            </div>
            <div class="card-body p-0">
              <div v-if="!visibleSections.length" class="p-3 text-center text-secondary" style="font-size:.85rem">
                No sections registered.
              </div>
              <nav v-else class="p-2">
                <button
                  v-for="section in visibleSections"
                  :key="section.name"
                  class="uim-panel__section-btn w-100 text-start d-flex align-items-center gap-2 mb-1"
                  :class="{ active: currentSection === section.name }"
                  @click="selectSection(section.name)"
                >
                  <span class="flex-grow-1 text-truncate">{{ section.label }}</span>
                  <span
                    v-if="section.repeatable"
                    class="badge text-bg-secondary rounded-pill"
                    style="font-size:.65rem"
                  >list</span>
                </button>
              </nav>
            </div>
          </div>
        </div>

        <!-- ── Section form card ──────────────────────────────────── -->
        <div class="col-md-9">
          <div class="card h-100" style="min-height:400px;border-radius:.5rem;background:#fff">
            <div v-if="activeSectionDef" class="card-header py-2 px-4 d-flex align-items-center justify-content-between">
              <div>
                <p class="mb-0 fw-semibold" style="font-size:.9rem">
                  {{ activeSectionDef.label }}
                </p>
                <p class="mb-0 text-secondary" style="font-size:.75rem">
                  {{ activeSectionDef.repeatable ? 'Manage list items below.' : 'Edit section fields.' }}
                </p>
              </div>
            </div>
            <div class="card-body p-4 overflow-auto">
              <template v-if="activeSectionDef">
                <SectionForm
                  v-if="!activeSectionDef.repeatable"
                  :key="activeSectionDef.name + currentPage"
                  :page="currentPage"
                  :section="activeSectionDef.name"
                  :definition="activeSectionDef"
                />
                <RepeatableSection
                  v-else
                  :key="activeSectionDef.name + currentPage"
                  :page="currentPage"
                  :section="activeSectionDef.name"
                  :definition="activeSectionDef"
                />
              </template>
              <div v-else class="d-flex align-items-center justify-content-center h-100 text-secondary" style="min-height:200px;font-size:.875rem">
                Select a section from the left panel.
              </div>
            </div>
          </div>
        </div>

      </div>
    </template>

    <!-- ── Empty state ────────────────────────────────────────────────── -->
    <div v-else class="text-center py-5 text-secondary">
      <p class="mb-0">No pages registered yet.</p>
    </div>

    <ToastContainer />
  </BsWrapper>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useUiStore } from '../stores/ui.js'
import { BsWrapper } from './bs/index.js'
import SectionForm from './SectionForm.vue'
import RepeatableSection from './repeatable/RepeatableSection.vue'
import ToastContainer from './layout/ToastContainer.vue'

const HASH_PREFIX = 'uim:'

function parseHash() {
  const raw = window.location.hash.slice(1)
  if (!raw.startsWith(HASH_PREFIX)) return { page: null, section: null }
  const parts = raw.slice(HASH_PREFIX.length).split(':')
  return { page: parts[0] || null, section: parts[1] || null }
}

function writeHash(page, section) {
  const hash = section
    ? `${HASH_PREFIX}${page}:${section}`
    : `${HASH_PREFIX}${page}`
  history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${hash}`)
}

const store = useUiStore()
const currentPage    = ref(null)
const currentSection = ref(null)

const currentPageData = computed(() => store.pageMap[currentPage.value] ?? null)

const visibleSections = computed(() =>
  currentPageData.value?.sections?.filter(s => s.visible) ?? []
)

const activeSectionDef = computed(() =>
  visibleSections.value.find(s => s.name === currentSection.value) ?? null
)

function selectPage(pageName, sectionName = null) {
  currentPage.value = pageName
  const sections = store.pageMap[pageName]?.sections?.filter(s => s.visible) ?? []
  currentSection.value = sectionName ?? sections[0]?.name ?? null
  writeHash(currentPage.value, currentSection.value)
}

function selectSection(sectionName) {
  currentSection.value = sectionName
  writeHash(currentPage.value, currentSection.value)
}

function applyHashOrDefaults() {
  const { page, section } = parseHash()
  const pages = store.pages
  if (!pages.length) return
  const targetPage = page && store.pageMap[page] ? page : pages[0]?.name
  if (!targetPage) return
  const sections = store.pageMap[targetPage]?.sections?.filter(s => s.visible) ?? []
  const targetSection = section && sections.find(s => s.name === section)
    ? section
    : sections[0]?.name ?? null
  currentPage.value    = targetPage
  currentSection.value = targetSection
  if (targetPage) writeHash(targetPage, targetSection)
}

function onHashChange() {
  const { page, section } = parseHash()
  if (!page) return
  if (page !== currentPage.value) {
    currentPage.value = page
    const sections = store.pageMap[page]?.sections?.filter(s => s.visible) ?? []
    currentSection.value = section ?? sections[0]?.name ?? null
  } else if (section && section !== currentSection.value) {
    currentSection.value = section
  }
}

onMounted(async () => {
  window.addEventListener('hashchange', onHashChange)
  if (!store.pages.length) {
    await Promise.all([store.fetchPages(), store.fetchVariables()])
  }
  applyHashOrDefaults()
})

onUnmounted(() => {
  window.removeEventListener('hashchange', onHashChange)
})

watch(() => store.pages, (pages) => {
  if (pages.length && !currentPage.value) applyHashOrDefaults()
})

watch(currentPage, () => {
  if (!activeSectionDef.value && visibleSections.value.length) {
    currentSection.value = visibleSections.value[0].name
  }
})
</script>

<style scoped>
.uim-panel__page-btn {
  padding: .45rem 1rem;
  border: 1px solid #0d6efd;
  background: transparent;
  border-radius: .375rem;
  font-size: .875rem;
  color: #0d6efd;
  transition: background .12s, color .12s, border-color .12s;
  cursor: pointer;
  white-space: nowrap;
}
.uim-panel__page-btn:hover {
  background: #e7f0ff;
  color: #0a58ca;
}
.uim-panel__page-btn.active {
  background: #0d6efd;
  border-color: #0d6efd;
  color: #fff;
  font-weight: 500;
}

.uim-panel__section-btn {
  padding: .5rem .75rem;
  border: none;
  background: transparent;
  border-radius: .375rem;
  font-size: .875rem;
  color: #495057;
  transition: background .12s, color .12s;
  cursor: pointer;
}
.uim-panel__section-btn:hover {
  background: #e9ecef;
  color: #212529;
}
.uim-panel__section-btn.active {
  background: #0d6efd;
  color: #fff;
  font-weight: 500;
}
.uim-panel__section-btn.active .badge {
  background: rgba(255,255,255,.25) !important;
  color: #fff;
}
</style>
