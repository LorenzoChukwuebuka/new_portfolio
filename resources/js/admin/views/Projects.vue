<template>
  <Layout page-title="Projects">
    <div class="space-y-6">
      <PageHeader title="Projects" description="Manage your public case studies, featured work and display order.">
        <template #actions><Button @click="$router.push('/admin/projects/create')">Add project</Button></template>
      </PageHeader>

      <Card class="space-y-4 p-4 sm:p-5">
        <div class="grid gap-3 md:grid-cols-3">
          <SearchInput v-model="search" :delay="0" placeholder="Search projects" />
          <Select id="project-status-filter" v-model="status" aria-label="Filter by status" @change="resetAndFetch"><option value="all">All statuses</option><option value="completed">Completed</option><option value="in-progress">In progress</option><option value="archived">Archived</option></Select>
          <Select id="project-featured-filter" v-model="featured" aria-label="Filter featured projects" @change="resetAndFetch"><option value="all">Featured and unfeatured</option><option value="true">Featured</option><option value="false">Not featured</option></Select>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-adm-line pt-3">
          <p class="text-sm text-adm-ink-muted">{{ pageInfo.total }} projects · ordered by portfolio position</p>
          <p v-if="canReorder" class="text-xs text-adm-ink-muted">Use the arrows to change display order.</p>
        </div>
      </Card>

      <ErrorBanner v-if="error" :message="error" :retry="fetchProjects" />
      <AdminLoader v-if="loading && projects.length === 0" title="Loading projects" message="Fetching portfolio projects and media." :rows="5" />
      <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" :aria-busy="loading">
        <Card v-for="(project, index) in projects" :key="project.id" class="overflow-hidden p-0">
          <div class="aspect-[16/9] overflow-hidden bg-adm-surface-2">
            <img v-if="thumbnail(project)" :src="thumbnail(project)" :alt="`${project.title} thumbnail`" loading="lazy" decoding="async" class="h-full w-full object-cover" />
            <div v-else class="grid h-full place-items-center text-sm text-adm-ink-muted">No thumbnail yet</div>
          </div>
          <div class="space-y-4 p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0"><h2 class="truncate text-lg font-semibold text-adm-ink">{{ project.title }}</h2><p class="mt-1 line-clamp-2 text-sm text-adm-ink-muted">{{ project.description }}</p></div>
              <Badge :variant="statusVariant(project.status)">{{ project.status.replace('-', ' ') }}</Badge>
            </div>
            <div class="flex flex-wrap gap-2"><Badge v-for="tech in project.technologies.slice(0, 4)" :key="tech">{{ tech }}</Badge><Badge v-if="project.technologies.length > 4">+{{ project.technologies.length - 4 }}</Badge></div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-adm-line pt-3">
              <label class="inline-flex min-h-10 items-center gap-2 text-sm text-adm-ink"><input type="checkbox" :checked="project.is_featured" :disabled="workingId === project.id" class="size-4 rounded border-adm-line text-adm-accent focus:ring-adm-focus" @change="setFeatured(project, $event)" />Featured</label>
              <div class="flex items-center gap-1">
                <button v-if="canReorder" type="button" class="grid size-10 place-items-center rounded-md border border-adm-line text-adm-ink hover:bg-adm-surface-2 disabled:opacity-40" :disabled="index === 0 || workingId === project.id" :aria-label="`Move ${project.title} up`" @click="move(index, -1)">↑</button>
                <button v-if="canReorder" type="button" class="grid size-10 place-items-center rounded-md border border-adm-line text-adm-ink hover:bg-adm-surface-2 disabled:opacity-40" :disabled="index === projects.length - 1 || workingId === project.id" :aria-label="`Move ${project.title} down`" @click="move(index, 1)">↓</button>
                <a class="grid size-10 place-items-center rounded-md px-2 text-sm font-medium text-adm-ink-muted underline-offset-2 hover:text-adm-ink hover:underline" :href="`/projects/${project.slug}`" target="_blank" rel="noopener noreferrer">View</a>
                <router-link class="grid min-h-10 place-items-center rounded-md px-2 text-sm font-medium text-adm-info-ink underline-offset-2 hover:underline" :to="`/admin/projects/${project.slug}/edit`">Edit</router-link>
                <Button size="sm" variant="danger" @click="deleteProject(project)">Delete</Button>
              </div>
            </div>
          </div>
        </Card>
        <EmptyState v-if="projects.length === 0 && !loading" title="No projects found" description="Change the filters or add a project." />
      </div>
      <Pagination v-if="pageInfo.total" :page="pageInfo.current_page" :last-page="pageInfo.last_page" :total="pageInfo.total" :from="pageInfo.from ?? undefined" :to="pageInfo.to ?? undefined" @change="changePage" />
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '../components/Layout.vue'
import AdminLoader from '../components/AdminLoader.vue'
import api, { apiErrorMessage } from '../utils/api'
import type { Project } from '../types'
import { confirmAction, showToast } from '../ui/feedback'
import PageHeader from '../ui/PageHeader.vue'
import Button from '../ui/Button.vue'
import Card from '../ui/Card.vue'
import Badge from '../ui/Badge.vue'
import SearchInput from '../ui/SearchInput.vue'
import Select from '../ui/Select.vue'
import Pagination from '../ui/Pagination.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'

interface Paginated<T> { data:T[]; current_page:number; last_page:number; total:number; from:number|null; to:number|null }
const projects = ref<Project[]>([])
const search = ref('')
const status = ref('all')
const featured = ref('all')
const page = ref(1)
const pageInfo = ref<Paginated<Project>>({ data:[], current_page:1, last_page:1, total:0, from:null, to:null })
const loading = ref(true)
const workingId = ref<number|null>(null)
const error = ref('')
let searchTimer:ReturnType<typeof setTimeout>
const canReorder = computed(() => !search.value && status.value === 'all' && featured.value === 'all')
onMounted(() => { void fetchProjects() })
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(resetAndFetch, 300) })

async function fetchProjects() {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/admin/projects', { params:{ search:search.value || undefined, status:status.value, is_featured:featured.value, page:page.value, per_page:50 } })
    pageInfo.value = data
    projects.value = data.data
  } catch (reason) { error.value = apiErrorMessage(reason) }
  finally { loading.value = false }
}
function resetAndFetch() { page.value = 1; void fetchProjects() }
function changePage(next:number) { page.value = next; void fetchProjects() }
function thumbnail(project:Project) {
  const media = (project.media || []).find((item:any) => item.type === 'thumbnail')
  const path = media?.thumbnail || media?.url
  if (!path) return ''
  return String(path).startsWith('http') || String(path).startsWith('/') ? path : `/storage/${path}`
}
function statusVariant(value:string) { return value === 'completed' ? 'success' : value === 'in-progress' ? 'info' : 'neutral' }
async function setFeatured(project:Project, event:Event) {
  const value = (event.target as HTMLInputElement).checked
  workingId.value = project.id || null
  try {
    await api.patch(`/admin/projects/${project.id}/featured`, { is_featured:value })
    project.is_featured = value
    showToast(value ? 'Project featured.' : 'Project unfeatured.')
    if (featured.value !== 'all') await fetchProjects()
  } catch (reason) { (event.target as HTMLInputElement).checked = !value; showToast(apiErrorMessage(reason), 'error') }
  finally { workingId.value = null }
}
async function move(index:number, direction:-1|1) {
  const next = index + direction
  if (next < 0 || next >= projects.value.length) return
  const reordered = [...projects.value]
  ;[reordered[index], reordered[next]] = [reordered[next], reordered[index]]
  workingId.value = reordered[next]?.id || null
  try {
    await api.post('/admin/projects/reorder', { projects:reordered.map((item, order) => ({ id:item.id, order:order + 1 })) })
    projects.value = reordered
    showToast('Project order saved.')
  } catch (reason) { showToast(apiErrorMessage(reason), 'error') }
  finally { workingId.value = null }
}
async function deleteProject(project:Project) {
  if (!await confirmAction(`Delete “${project.title}”? This removes the project from the portfolio.`)) return
  try { await api.delete(`/admin/projects/${project.slug}`); await fetchProjects(); showToast('Project deleted.') }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
</script>
