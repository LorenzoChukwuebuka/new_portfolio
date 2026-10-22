<template>
  <div class="admin-shell min-h-svh bg-adm-bg text-adm-ink">
    <div v-if="drawerOpen" class="fixed inset-0 z-[var(--z-drawer)] bg-adm-sidebar-bg/65 lg:hidden" @click="closeDrawer"/>
    <aside id="admin-navigation" class="admin-sidebar fixed inset-y-0 left-0 z-[calc(var(--z-drawer)+1)] flex w-[min(18rem,88vw)] -translate-x-full flex-col border-r border-adm-line bg-adm-sidebar-bg text-adm-sidebar-ink transition-transform duration-200 lg:z-30 lg:translate-x-0" :class="[drawerOpen ? '!translate-x-0' : '', collapsed ? 'lg:w-[5rem]' : 'lg:w-64']" :inert="isMobile && !drawerOpen" :aria-modal="isMobile && drawerOpen ? 'true' : undefined" :role="isMobile ? 'dialog' : 'complementary'" aria-label="Admin navigation">
      <div class="flex h-16 items-center justify-between gap-3 border-b border-adm-sidebar-ink/10 px-4">
        <router-link class="flex min-w-0 items-center gap-3 font-semibold" to="/admin/dashboard" @click="closeDrawer"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-adm-accent-bg text-adm-accent-ink">L.</span><span v-if="!collapsed || isMobile" class="truncate">Portfolio CMS</span></router-link>
        <button v-if="isMobile" ref="closeButton" class="grid h-10 w-10 place-items-center rounded-md hover:bg-adm-sidebar-ink/10" aria-label="Close navigation" @click="closeDrawer">×</button>
        <button v-else class="hidden h-9 w-9 place-items-center rounded-md text-adm-sidebar-ink hover:bg-adm-sidebar-ink/10 lg:grid" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="toggleCollapsed">{{ collapsed ? '»' : '«' }}</button>
      </div>
      <nav class="flex-1 overflow-y-auto px-3 py-5">
        <template v-for="group in groups" :key="group.label">
          <p v-if="!collapsed || isMobile" class="mb-2 mt-5 px-3 text-[11px] font-semibold uppercase tracking-[.16em] text-adm-sidebar-ink/55 first:mt-0">{{ group.label }}</p>
          <div class="grid gap-1">
            <router-link v-for="item in group.items" :key="item.path" :to="item.path" class="group flex min-h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors" :class="isActive(item.path) ? 'bg-adm-accent-bg text-adm-accent-ink' : 'text-adm-sidebar-ink/75 hover:bg-adm-sidebar-ink/10 hover:text-adm-sidebar-ink'" :title="collapsed && !isMobile ? item.label : undefined" @click="closeDrawer">
              <Icon :name="item.icon" class-name="h-5 w-5 shrink-0" :class="isActive(item.path) ? 'text-adm-accent-ink' : 'text-adm-sidebar-ink/65 group-hover:text-adm-sidebar-ink'"/>
              <span v-if="!collapsed || isMobile" class="min-w-0 flex-1 truncate">{{ item.label }}</span>
              <span v-if="badgeCount(item.badge) && (!collapsed || isMobile)" class="rounded-full bg-adm-warning-bg px-2 py-0.5 text-[11px] font-bold text-adm-warning-ink">{{ badgeCount(item.badge) }}</span>
              <span v-else-if="badgeCount(item.badge) && collapsed && !isMobile" class="ml-auto h-2 w-2 rounded-full bg-adm-accent-bg" aria-label="Items need review"/>
            </router-link>
          </div>
        </template>
      </nav>
      <div class="grid gap-2 border-t border-adm-sidebar-ink/10 p-3">
        <a href="/" class="flex min-h-10 items-center gap-3 rounded-md border border-adm-sidebar-ink/15 px-3 text-sm text-adm-sidebar-ink/80 hover:bg-adm-sidebar-ink/10"><span aria-hidden="true">↗</span><span v-if="!collapsed || isMobile">View site</span></a>
        <div class="flex items-center justify-between gap-2 px-1"><span v-if="!collapsed || isMobile" class="truncate text-xs text-adm-sidebar-ink/65">{{ accountLabel }}</span><button class="min-h-10 rounded-md px-3 text-xs font-semibold text-adm-sidebar-ink/80 hover:bg-adm-sidebar-ink/10" @click="logout">{{ !collapsed || isMobile ? 'Sign out' : '↪' }}</button></div>
      </div>
    </aside>

    <div class="min-h-svh transition-[padding] duration-200" :class="collapsed ? 'lg:pl-20' : 'lg:pl-64'">
      <div class="fixed left-0 right-0 top-0 z-[var(--z-toast)] h-0.5 overflow-hidden bg-transparent lg:left-64" :class="collapsed ? 'lg:left-20' : ''"><span class="admin-route-progress" :class="navigating ? 'is-loading' : ''"/></div>
      <header class="sticky top-0 z-20 border-b border-adm-line bg-adm-surface/95 backdrop-blur">
        <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-3">
            <button ref="menuButton" class="grid h-10 w-10 shrink-0 place-items-center rounded-md border border-adm-line text-adm-ink lg:hidden" aria-label="Open admin navigation" aria-controls="admin-navigation" :aria-expanded="drawerOpen" @click="openDrawer"><Icon name="menu" class-name="h-5 w-5"/></button>
            <div class="min-w-0"><p class="text-[11px] font-semibold uppercase tracking-[.15em] text-adm-ink-muted">Workspace <span v-if="breadcrumb">/ {{ breadcrumb }}</span></p><h1 class="truncate text-lg font-semibold tracking-tight sm:text-xl">{{ pageTitle }}</h1></div>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <details class="relative"><summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-md border border-adm-line px-3 text-sm font-medium hover:bg-adm-surface-2"><Icon name="plus" class-name="h-4 w-4"/><span class="hidden sm:inline">Create</span><span class="sm:hidden">New</span></summary><div class="absolute right-0 z-30 mt-2 grid w-48 rounded-md border border-adm-line bg-adm-surface p-1 shadow-adm-card"><router-link class="rounded px-3 py-2 text-sm hover:bg-adm-surface-2" to="/admin/posts/create">New post</router-link><router-link class="rounded px-3 py-2 text-sm hover:bg-adm-surface-2" to="/admin/projects/create">New project</router-link></div></details>
            <ThemeToggle/>
          </div>
        </div>
      </header>
      <main id="admin-main" class="mx-auto w-full max-w-[100rem] px-4 py-5 sm:px-6 lg:px-8" :inert="drawerOpen">
        <Transition name="admin-page" mode="out-in"><div :key="route.fullPath"><slot/></div></Transition>
      </main>
    </div>
    <ToastHost/><ConfirmDialog/>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../utils/api'
import Icon from '../ui/Icon.vue'
import ThemeToggle from '../ui/ThemeToggle.vue'
import ToastHost from '../ui/ToastHost.vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

const props = defineProps<{ pageTitle: string }>()
const route = useRoute()
const router = useRouter()
const isMobile = ref(false)
const drawerOpen = ref(false)
const collapsed = ref(false)
const unread = ref(0)
const pending = ref(0)
const navigating = ref(false)
const menuButton = ref<HTMLButtonElement>()
const closeButton = ref<HTMLButtonElement>()
let polling: ReturnType<typeof setInterval> | undefined
let media: MediaQueryList
let returnFocus: HTMLElement | null = null

type NavIcon = 'grid' | 'file' | 'folder' | 'tag' | 'hash' | 'mail' | 'chat' | 'download'
const groups: { label: string; items: { label: string; path: string; icon: NavIcon; badge?: string }[] }[] = [
  { label: 'Overview', items: [{ label: 'Dashboard', path: '/admin/dashboard', icon: 'grid' as const }] },
  { label: 'Content', items: [{ label: 'Posts', path: '/admin/posts', icon: 'file' as const }, { label: 'Projects', path: '/admin/projects', icon: 'folder' as const }, { label: 'Categories', path: '/admin/categories', icon: 'tag' as const }, { label: 'Tags', path: '/admin/tags', icon: 'hash' as const }] },
  { label: 'Inbox', items: [{ label: 'Contacts', path: '/admin/contacts', icon: 'mail' as const, badge: 'contacts' }, { label: 'Comments', path: '/admin/comments', icon: 'chat' as const, badge: 'comments' }] },
  { label: 'Assets', items: [{ label: 'CV library', path: '/admin/cv', icon: 'download' as const }] },
]
const accountLabel = computed(() => localStorage.getItem('admin_name') || 'Administrator')
const breadcrumb = computed(() => route.name === 'Dashboard' ? 'Overview' : String(route.meta.group || route.name || 'Admin'))
function badgeCount(type?: string) { return type === 'contacts' ? unread.value : type === 'comments' ? pending.value : 0 }
function isActive(path: string) { return path === '/admin/dashboard' ? route.path === path : route.path.startsWith(path) }
function updateViewport() { isMobile.value = !media.matches }
function toggleCollapsed() { collapsed.value = !collapsed.value; localStorage.setItem('admin_sidebar_collapsed', collapsed.value ? '1' : '0') }
function closeDrawer() { if (!drawerOpen.value) return; drawerOpen.value = false; document.body.classList.remove('admin-drawer-open'); nextTick(() => (returnFocus || menuButton.value)?.focus()) }
function openDrawer() { returnFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null; drawerOpen.value = true; document.body.classList.add('admin-drawer-open'); nextTick(() => closeButton.value?.focus()) }
function keydown(event: KeyboardEvent) {
  if (drawerOpen.value && event.key === 'Escape') { event.preventDefault(); closeDrawer(); return }
  if (drawerOpen.value && event.key === 'Tab') {
    const panel = document.getElementById('admin-navigation')
    const items = Array.from(panel?.querySelectorAll<HTMLElement>('a[href],button:not([disabled])') || []).filter((element) => !element.inert)
    if (!items.length) return
    if (event.shiftKey && document.activeElement === items[0]) { event.preventDefault(); items.at(-1)?.focus() }
    else if (!event.shiftKey && document.activeElement === items.at(-1)) { event.preventDefault(); items[0].focus() }
  }
}
async function loadBadges() {
  if (document.visibilityState !== 'visible' || !api.isAuthenticated()) return
  const [contactResult, commentResult] = await Promise.allSettled([api.get('/admin/contact-messages/unread-count'), api.get('/admin/comments/stats')])
  if (contactResult.status === 'fulfilled') unread.value = Number(contactResult.value.data.count ?? contactResult.value.data.unread ?? 0)
  if (commentResult.status === 'fulfilled') pending.value = Number(commentResult.value.data.pending || 0)
}
async function logout() { try { await api.logout() } finally { await router.push('/admin/login') } }
function onNavigate(event: Event) { navigating.value = (event as CustomEvent<boolean>).detail }
function shortcut(event: KeyboardEvent) { if (event.key === 'g' && !event.metaKey && !event.ctrlKey && !event.altKey) { window.setTimeout(() => { if (document.activeElement?.tagName === 'BODY') { /* next key handled by browser navigation links */ } }, 0) } }
watch(() => route.fullPath, closeDrawer)
watch(() => props.pageTitle, (title) => { document.title = `${title} · Portfolio` }, { immediate: true })
onMounted(() => {
  media = window.matchMedia('(min-width: 1024px)')
  updateViewport()
  media.addEventListener('change', updateViewport)
  collapsed.value = localStorage.getItem('admin_sidebar_collapsed') === '1'
  void loadBadges()
  polling = window.setInterval(loadBadges, 60_000)
  window.addEventListener('keydown', keydown)
  window.addEventListener('keydown', shortcut)
  document.addEventListener('visibilitychange', loadBadges)
  window.addEventListener('admin:navigating', onNavigate)
  window.addEventListener('admin:unauthenticated', () => { void router.push('/admin/login') })
})
onBeforeUnmount(() => {
  if (polling) clearInterval(polling)
  media?.removeEventListener('change', updateViewport)
  window.removeEventListener('keydown', keydown)
  window.removeEventListener('keydown', shortcut)
  document.removeEventListener('visibilitychange', loadBadges)
  window.removeEventListener('admin:navigating', onNavigate)
  document.body.classList.remove('admin-drawer-open')
})
</script>
