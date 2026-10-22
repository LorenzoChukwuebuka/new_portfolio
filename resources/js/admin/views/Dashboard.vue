<template>
  <Layout page-title="Dashboard">
    <div class="admin-page">
    <PageHeader title="Dashboard" description="A clear read on portfolio activity and the work that needs your attention.">
      <template #actions>
        <label class="sr-only" for="dashboard-days">Analytics period</label>
        <select id="dashboard-days" v-model.number="days" class="rounded-md border border-adm-line bg-adm-surface px-3 py-2 text-sm text-adm-ink" @change="loadVisits">
          <option :value="7">Last 7 days</option><option :value="14">Last 14 days</option><option :value="30">Last 30 days</option><option :value="90">Last 90 days</option>
        </select>
      </template>
    </PageHeader>

    <Card v-if="attentionCount" class="mb-5 border-l-4 border-l-adm-accent">
      <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold text-adm-ink">Needs your attention</p><p class="mt-1 text-sm text-adm-ink-muted">{{ attentionCount }} items are waiting in the portfolio workspace.</p></div><div class="flex flex-wrap gap-2"><router-link v-if="unreadContacts" class="rounded-md bg-adm-warning-bg px-3 py-2 text-sm font-semibold text-adm-warning-ink" to="/admin/contacts">{{ unreadContacts }} unread messages</router-link><router-link v-if="pendingComments" class="rounded-md bg-adm-info-bg px-3 py-2 text-sm font-semibold text-adm-info-ink" to="/admin/comments">{{ pendingComments }} comments to review</router-link><router-link v-if="draftCount" class="rounded-md bg-adm-surface-2 px-3 py-2 text-sm font-semibold text-adm-ink" to="/admin/posts?status=draft">{{ draftCount }} drafts</router-link></div></div>
    </Card>

    <ErrorBanner v-if="stats.error" :message="stats.error" :retry="loadStats" class="mb-4"/>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="item in kpis" :key="item.label"><p class="text-xs font-semibold uppercase tracking-wider text-adm-ink-muted">{{ item.label }}</p><p v-if="stats.loading" class="mt-3 h-8 w-24 animate-pulse rounded bg-adm-surface-2" aria-label="Loading KPI"/><p v-else class="mt-2 text-3xl font-semibold tracking-tight text-adm-ink">{{ item.value }}</p><p class="mt-1 text-xs text-adm-ink-muted">{{ item.detail }}</p></Card>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(18rem,0.75fr)]">
      <Card>
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-base font-semibold text-adm-ink">Visits and unique visitors</h2><p class="mt-1 text-sm text-adm-ink-muted">Daily traffic for the selected period</p></div><Badge variant="info">{{ visitsTotal }} visits</Badge></div>
        <div v-if="visits.loading" class="mt-5 h-60 animate-pulse rounded-md bg-adm-surface-2" aria-label="Loading visits chart"/>
        <ErrorBanner v-else-if="visits.error" class="mt-4" :message="visits.error" :retry="loadVisits"/>
        <div v-else-if="visits.data.length" class="mt-4">
          <div class="flex flex-wrap gap-4 text-xs text-adm-ink-muted"><span><i class="mr-2 inline-block h-2 w-2 rounded-full bg-adm-accent-bg"/>Visits</span><span><i class="mr-2 inline-block h-2 w-2 rounded-full bg-adm-info-ink"/>Unique visitors</span><span class="ml-auto">{{ uniqueTotal }} unique</span></div>
          <div class="mt-3 overflow-x-auto"><svg class="admin-chart" viewBox="0 0 900 240" role="img" :aria-label="`Visits and unique visitors over ${days} days`" preserveAspectRatio="none"><defs><linearGradient id="visit-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="var(--adm-accent-bg)" stop-opacity=".28"/><stop offset="1" stop-color="var(--adm-accent-bg)" stop-opacity="0"/></linearGradient></defs><path :d="areaPath('visits')" fill="url(#visit-fill)"/><polyline :points="linePoints('visits')" fill="none" stroke="var(--adm-accent-bg)" stroke-width="3" vector-effect="non-scaling-stroke"/><polyline :points="linePoints('unique')" fill="none" stroke="var(--adm-info-ink)" stroke-width="2" stroke-dasharray="6 6" vector-effect="non-scaling-stroke"/><g v-for="(item,index) in visits.data" :key="`${item.date}-${index}`" class="chart-point" tabindex="0" @mouseenter="hovered=index" @focus="hovered=index" @click="hovered=index"><circle :cx="x(index)" :cy="y(item.visits)" r="4" fill="var(--adm-accent-bg)"/><title>{{ item.date }} — {{ item.visits }} visits, {{ item.unique }} unique</title></g></svg></div>
          <div class="mt-2 flex justify-between text-[11px] text-adm-ink-muted"><span>{{ visits.data[0]?.date }}</span><span v-if="hovered !== null">{{ visits.data[hovered]?.date }}: {{ visits.data[hovered]?.visits }} visits · {{ visits.data[hovered]?.unique }} unique</span><span>{{ visits.data.at(-1)?.date }}</span></div>
        </div>
        <EmptyState v-else class="mt-4" title="No visits in this period" description="Page visits will appear as people explore the public site."/>
      </Card>

      <Card>
        <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-semibold text-adm-ink">Popular pages</h2><p class="mt-1 text-sm text-adm-ink-muted">Most visited routes</p></div><span v-if="pages.loading" class="h-5 w-12 animate-pulse rounded bg-adm-surface-2"/></div>
        <ErrorBanner v-if="pages.error" class="mt-4" :message="pages.error" :retry="loadPages"/>
        <ol v-else-if="pages.data.length" class="mt-4 divide-y divide-adm-line"> <li v-for="page in pages.data.slice(0,6)" :key="page.path" class="py-3"><div class="flex items-center justify-between gap-3"><span class="min-w-0 truncate text-sm font-medium text-adm-ink" :title="page.path">{{ page.path }}</span><Badge>{{ page.visits }}</Badge></div><p class="mt-1 text-xs text-adm-ink-muted">{{ page.unique_visitors }} unique visitors</p></li></ol>
        <EmptyState v-else-if="!pages.loading && !pages.error" class="mt-4" title="No pages yet"/>
      </Card>
      <Card>
        <div><h2 class="text-base font-semibold text-adm-ink">Referrer sources</h2><p class="mt-1 text-sm text-adm-ink-muted">Where visits came from</p></div>
        <ErrorBanner v-if="visitors.error" class="mt-4" :message="visitors.error" :retry="loadVisitors"/>
        <div v-else-if="visitors.loading" class="mt-4 h-40 animate-pulse rounded-md bg-adm-surface-2" aria-label="Loading visitor sources"/>
        <ol v-else class="mt-4 divide-y divide-adm-line"><li v-for="source in visitors.data.referrers" :key="source.source" class="flex items-center justify-between gap-3 py-3"><span class="min-w-0 truncate text-sm text-adm-ink">{{ source.source }}</span><span class="shrink-0 text-xs text-adm-ink-muted">{{ source.visits }} · {{ source.unique_visitors }} unique</span></li><li v-if="!visitors.data.referrers.length" class="py-4 text-sm text-adm-ink-muted">No referrers recorded in this period.</li></ol>
      </Card>
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-2">
      <Card>
        <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-semibold text-adm-ink">Post performance</h2><p class="mt-1 text-sm text-adm-ink-muted">Views and discussion status</p></div><router-link class="text-sm font-semibold text-adm-accent" to="/admin/posts">Manage posts →</router-link></div>
        <div v-if="performance.loading" class="mt-4 grid gap-2"><div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-adm-surface-2"/></div>
        <ErrorBanner v-else-if="performance.error" class="mt-4" :message="performance.error" :retry="loadPerformance"/>
        <div v-else class="mt-4 divide-y divide-adm-line"><router-link v-for="post in performance.data.slice(0,6)" :key="post.id" class="flex min-h-14 items-center justify-between gap-3 py-2 text-sm hover:text-adm-accent" :to="`/admin/posts/${post.slug}/edit`"><span class="min-w-0 truncate font-medium text-adm-ink">{{ post.title }}</span><span class="shrink-0 text-xs text-adm-ink-muted">{{ post.views_count }} views · {{ post.approved_comments_count }} approved · {{ post.pending_comments_count }} pending</span></router-link><EmptyState v-if="!performance.data.length" title="No posts yet" description="Create a post to see engagement here."/></div>
      </Card>
      <Card>
        <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-semibold text-adm-ink">Recent activity</h2><p class="mt-1 text-sm text-adm-ink-muted">Latest changes across your workspace</p></div><router-link to="/admin/posts/create" class="rounded-md bg-adm-accent-bg px-3 py-2 text-sm font-semibold text-adm-accent-ink">New post</router-link></div>
        <div v-if="activity.loading" class="mt-4 grid gap-2"><div v-for="n in 4" :key="n" class="h-12 animate-pulse rounded bg-adm-surface-2"/></div>
        <ErrorBanner v-else-if="activity.error" class="mt-4" :message="activity.error" :retry="loadActivity"/>
        <ol v-else class="mt-4 divide-y divide-adm-line"><li v-for="(item,index) in activity.data.slice(0,7)" :key="`${item.type}-${item.title}-${index}`" class="flex items-start justify-between gap-3 py-3"><div class="min-w-0"><p class="truncate text-sm font-medium text-adm-ink">{{ item.title }}</p><p class="text-xs capitalize text-adm-ink-muted">{{ item.type }} · {{ item.status }}</p></div><time class="shrink-0 text-xs text-adm-ink-muted" :datetime="item.date">{{ relative(item.date) }}</time></li></ol>
      </Card>
    </div>

    <div class="mt-5 flex flex-wrap gap-3"><router-link to="/admin/posts/create" class="rounded-md bg-adm-accent-bg px-4 py-3 text-sm font-semibold text-adm-accent-ink">Write a post</router-link><router-link to="/admin/projects/create" class="rounded-md border border-adm-line bg-adm-surface px-4 py-3 text-sm font-semibold text-adm-ink">Add a project</router-link><router-link to="/admin/contacts" class="rounded-md border border-adm-line bg-adm-surface px-4 py-3 text-sm font-semibold text-adm-ink">Open inbox</router-link></div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import Layout from '../components/Layout.vue'
import api from '../utils/api'
import Badge from '../ui/Badge.vue'
import Card from '../ui/Card.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'
import PageHeader from '../ui/PageHeader.vue'

interface DailyVisit { date: string; visits: number; unique: number }
interface PageRow { path: string; visits: number; unique_visitors: number }
interface PostRow { id: number; title: string; slug: string; views_count: number; approved_comments_count: number; pending_comments_count: number }
interface ActivityRow { type: string; title: string; status: string; date: string }
const days = ref(14)
const hovered = ref<number | null>(null)
const stats = reactive<{ loading: boolean; error: string; data: any }>({ loading: true, error: '', data: null })
const visits = reactive<{ loading: boolean; error: string; data: DailyVisit[] }>({ loading: true, error: '', data: [] })
const pages = reactive<{ loading: boolean; error: string; data: PageRow[] }>({ loading: true, error: '', data: [] })
const visitors = reactive<{ loading: boolean; error: string; data: { unique_visitors: number; referrers: { source: string; visits: number; unique_visitors: number }[] } }>({ loading: true, error: '', data: { unique_visitors: 0, referrers: [] } })
const performance = reactive<{ loading: boolean; error: string; data: PostRow[] }>({ loading: true, error: '', data: [] })
const activity = reactive<{ loading: boolean; error: string; data: ActivityRow[] }>({ loading: true, error: '', data: [] })
const unreadContacts = computed(() => Number(stats.data?.contacts?.unread || 0))
const pendingComments = computed(() => Number(stats.data?.comments?.pending || 0))
const draftCount = computed(() => Number(stats.data?.posts?.draft || 0))
const attentionCount = computed(() => unreadContacts.value + pendingComments.value + draftCount.value)
const visitsTotal = computed(() => visits.data.reduce((sum, item) => sum + Number(item.visits || 0), 0))
const uniqueTotal = computed(() => visitors.data.unique_visitors)
const kpis = computed(() => [
  { label: `${days.value}-day visits`, value: visitsTotal.value, detail: `${uniqueTotal.value} unique visitors` },
  { label: 'Published posts', value: stats.data?.posts?.published ?? '—', detail: `${draftCount.value} drafts need review` },
  { label: 'Projects', value: stats.data?.projects?.total ?? '—', detail: `${stats.data?.projects?.featured ?? 0} featured` },
  { label: 'Unread messages', value: unreadContacts.value, detail: `${pendingComments.value} comments awaiting review` },
])
const maxVisits = computed(() => Math.max(...visits.data.map((item) => item.visits), 1))
const x = (index: number) => visits.data.length < 2 ? 450 : 24 + (index * 852 / (visits.data.length - 1))
const y = (value: number) => 210 - (Math.max(0, value) / maxVisits.value) * 184
const linePoints = (key: 'visits' | 'unique') => visits.data.map((item, index) => `${x(index)},${y(item[key])}`).join(' ')
const areaPath = (key: 'visits' | 'unique') => visits.data.length ? `M ${x(0)} 220 L ${visits.data.map((item, index) => `${x(index)} ${y(item[key])}`).join(' L ')} L ${x(visits.data.length - 1)} 220 Z` : ''
async function loadStats() { stats.loading = true; stats.error = ''; try { const { data } = await api.get('/admin/dashboard'); stats.data = data } catch { stats.error = 'Dashboard totals could not be loaded.' } finally { stats.loading = false } }
async function loadVisits() { visits.loading = true; visits.error = ''; try { const { data } = await api.get('/admin/dashboard/page-visits-analytics', { params: { days: days.value } }); visits.data = data } catch { visits.error = 'Visit analytics could not be loaded.' } finally { visits.loading = false } }
async function loadPages() { pages.loading = true; pages.error = ''; try { const { data } = await api.get('/admin/dashboard/top-pages', { params: { days: days.value } }); pages.data = data } catch { pages.error = 'Top pages could not be loaded.' } finally { pages.loading = false } }
async function loadVisitors() { visitors.loading = true; visitors.error = ''; try { const { data } = await api.get('/admin/dashboard/referrer-sources', { params: { days: days.value } }); visitors.data = data } catch { visitors.error = 'Visitor sources could not be loaded.' } finally { visitors.loading = false } }
async function loadPerformance() { performance.loading = true; performance.error = ''; try { const { data } = await api.get('/admin/dashboard/post-performance'); performance.data = data } catch { performance.error = 'Post performance could not be loaded.' } finally { performance.loading = false } }
async function loadActivity() { activity.loading = true; activity.error = ''; try { const { data } = await api.get('/admin/dashboard/recent-activities'); activity.data = data } catch { activity.error = 'Recent activity could not be loaded.' } finally { activity.loading = false } }
function relative(value: string) { const minutes = Math.round((Date.now() - new Date(value).getTime()) / 60000); if (minutes < 1) return 'just now'; if (minutes < 60) return `${minutes}m ago`; const hours = Math.round(minutes / 60); return hours < 24 ? `${hours}h ago` : `${Math.round(hours / 24)}d ago` }
onMounted(() => { void Promise.allSettled([loadStats(), loadVisits(), loadPages(), loadVisitors(), loadPerformance(), loadActivity()]) })
</script>
