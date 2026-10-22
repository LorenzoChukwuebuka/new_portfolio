import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import '../css/app.css'
import App from './app.vue'
import routes from './admin/router'
import api from './admin/utils/api'

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    return to.hash ? { el: to.hash, behavior: 'smooth', top: 80 } : { top: 0 }
  },
})

router.beforeEach((to) => {
  window.dispatchEvent(new CustomEvent('admin:navigating', { detail: true }))
  const authenticated = api.isAuthenticated()
  if (to.meta.requiresAuth && !authenticated) return '/admin/login'
  if (to.meta.requiresGuest && authenticated) return '/admin/dashboard'
  return true
})

router.afterEach((to) => {
  window.dispatchEvent(new CustomEvent('admin:navigating', { detail: false }))
  const titles: Record<string, string> = {
    Dashboard: 'Dashboard', Login: 'Admin sign in', Categories: 'Categories', Posts: 'Posts',
    CreatePost: 'New post', EditPost: 'Edit post', Projects: 'Projects', CreateProject: 'New project',
    EditProject: 'Edit project', tags: 'Tags', cv: 'CV', contacts: 'Contacts', comments: 'Comments',
  }
  document.title = `${titles[String(to.name)] || 'Admin'} · Portfolio`
})

createApp(App).use(router).mount('#app')
