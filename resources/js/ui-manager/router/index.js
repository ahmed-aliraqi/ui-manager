import { createRouter, createWebHistory } from 'vue-router'

const config = window.__UI_MANAGER_CONFIG__ || {}
const base = '/' + (config.routePrefix || 'ui-manager')

const routes = [
  {
    path: '/',
    name: 'home',
    redirect: { name: 'pages' },
  },
  {
    path: '/pages',
    name: 'pages',
    component: () => import('../pages/PagesIndex.vue'),
  },
  {
    path: '/pages/:page',
    name: 'page-show',
    component: () => import('../pages/PageShow.vue'),
    props: true,
  },
  {
    path: '/pages/:page/sections/:section',
    name: 'section-edit',
    component: () => import('../pages/SectionEdit.vue'),
    props: true,
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'pages' },
  },
]

export const router = createRouter({
  history: createWebHistory(base),
  routes,
})
