<template>
  <Layout page-title="Comments">
    <div class="space-y-6">
      <PageHeader title="Comment moderation" description="Review replies and keep the discussion useful." />
      <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <Card v-for="card in statCards" :key="card.label" class="p-4">
          <p class="text-xs font-semibold uppercase tracking-widest text-adm-ink-muted">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-semibold text-adm-ink">{{ card.value }}</p>
        </Card>
      </div>

      <Card class="space-y-4 p-4 sm:p-5">
        <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-center">
          <div><h2 class="text-lg font-semibold text-adm-ink">Discussion queue</h2><p class="text-sm text-adm-ink-muted">Pending comments are shown first.</p></div>
          <div class="grid gap-3 sm:grid-cols-2">
            <SearchInput v-model="search" placeholder="Search comments" />
            <Select id="comment-status" v-model="status" aria-label="Comment status" @change="resetAndFetch"><option value="pending">Pending</option><option value="all">All comments</option><option value="approved">Approved</option><option value="spam">Spam</option></Select>
          </div>
        </div>
        <div v-if="selected.length" class="flex flex-wrap items-center gap-2 border-t border-adm-line pt-3">
          <span class="mr-1 text-sm text-adm-ink-muted">{{ selected.length }} selected</span>
          <Button size="sm" variant="secondary" @click="bulkStatus('approve')">Approve</Button>
          <Button size="sm" variant="secondary" @click="bulkStatus('spam')">Mark spam</Button>
          <Button size="sm" variant="danger" @click="bulkDelete">Delete</Button>
          <Button size="sm" variant="ghost" @click="selected = []">Clear</Button>
        </div>
      </Card>

      <ErrorBanner v-if="error" :message="error" :retry="fetchComments" />
      <AdminLoader v-if="loading && comments.length === 0" title="Loading comments" message="Fetching the moderation queue." :rows="5" />
      <div v-else class="space-y-3" :aria-busy="loading">
        <Card v-for="comment in comments" :key="comment.id" class="p-4 sm:p-5">
          <div class="flex gap-3">
            <input v-model="selected" type="checkbox" :value="comment.id" :aria-label="`Select comment from ${comment.author_name}`" class="mt-2 size-4 shrink-0 rounded border-adm-line text-adm-accent focus:ring-adm-focus" />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <p class="font-semibold text-adm-ink">{{ comment.author_name }}</p>
                <a class="break-all text-sm text-adm-ink-muted underline-offset-2 hover:underline" :href="`mailto:${comment.author_email}`">{{ comment.author_email }}</a>
                <Badge :variant="badgeVariant(comment.status)">{{ comment.status }}</Badge>
                <Badge v-if="comment.parent_id" variant="neutral">Reply</Badge>
              </div>
              <p class="mt-2 text-xs text-adm-ink-muted">On <a v-if="comment.post" :href="`/blog/${comment.post.slug}`" target="_blank" rel="noopener noreferrer" class="font-medium underline underline-offset-2">{{ comment.post.title }}</a><span v-else>Deleted post</span> · {{ formatDate(comment.created_at) }}</p>
              <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-adm-ink">{{ comment.body }}</p>
              <div class="mt-4 flex flex-wrap gap-2">
                <Button v-if="comment.status !== 'approved'" size="sm" variant="secondary" @click="changeStatus(comment.id, 'approve')">Approve</Button>
                <Button v-if="comment.status !== 'pending'" size="sm" variant="secondary" @click="changeStatus(comment.id, 'pending')">Move to pending</Button>
                <Button v-if="comment.status !== 'spam'" size="sm" variant="secondary" @click="changeStatus(comment.id, 'spam')">Spam</Button>
                <Button size="sm" variant="danger" @click="deleteComment(comment)">Delete</Button>
              </div>
            </div>
          </div>
        </Card>
        <EmptyState v-if="comments.length === 0 && !loading" title="No comments here" description="Try another status or search term." />
        <Pagination v-if="pageInfo" :page="pageInfo.current_page" :last-page="pageInfo.last_page" :total="pageInfo.total" :from="pageInfo.from ?? undefined" :to="pageInfo.to ?? undefined" @change="changePage" />
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Layout from '../components/Layout.vue'
import AdminLoader from '../components/AdminLoader.vue'
import api, { apiErrorMessage } from '../utils/api'
import { confirmAction, showToast } from '../ui/feedback'
import PageHeader from '../ui/PageHeader.vue'
import Card from '../ui/Card.vue'
import Badge from '../ui/Badge.vue'
import Button from '../ui/Button.vue'
import SearchInput from '../ui/SearchInput.vue'
import Select from '../ui/Select.vue'
import Pagination from '../ui/Pagination.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'

interface CommentItem { id:number; parent_id:number|null; author_name:string; author_email:string; body:string; status:'pending'|'approved'|'spam'; created_at:string; post?:{title:string;slug:string} }
interface PageInfo { current_page:number; last_page:number; total:number; from:number|null; to:number|null }
const comments = ref<CommentItem[]>([])
const stats = ref({ total:0, pending:0, approved:0, spam:0 })
const status = ref('pending')
const search = ref('')
const page = ref(1)
const pageInfo = ref<PageInfo | null>(null)
const selected = ref<number[]>([])
const loading = ref(true)
const error = ref('')
const statCards = computed(() => [
  { label:'All comments', value:stats.value.total },
  { label:'Pending', value:stats.value.pending },
  { label:'Approved', value:stats.value.approved },
  { label:'Spam', value:stats.value.spam },
])
onMounted(() => { void Promise.all([fetchComments(), fetchStats()]) })
watch(search, () => { page.value = 1; void fetchComments() })

async function fetchStats() {
  try { const { data } = await api.get('/admin/comments/stats'); stats.value = data }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
async function fetchComments() {
  loading.value = true; error.value = ''
  try {
    const { data } = await api.get('/admin/comments', { params: { status:status.value, search:search.value, page:page.value, per_page:20 } })
    comments.value = data.data
    pageInfo.value = { current_page:data.current_page, last_page:data.last_page, total:data.total, from:data.from, to:data.to }
    selected.value = []
  } catch (reason) { error.value = apiErrorMessage(reason) }
  finally { loading.value = false }
}
function resetAndFetch() { page.value = 1; void fetchComments() }
function changePage(nextPage:number) { page.value = nextPage; void fetchComments() }
async function changeStatus(id:number, action:string) {
  try { await api.post(`/admin/comments/${id}/${action}`); await Promise.all([fetchComments(), fetchStats()]); showToast('Comment updated.') }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
async function bulkStatus(action:'approve'|'spam') {
  const ids = [...selected.value]
  try { await Promise.all(ids.map(id => api.post(`/admin/comments/${id}/${action}`))); await Promise.all([fetchComments(), fetchStats()]); showToast(`${ids.length} comments updated.`) }
  catch (reason) { showToast(apiErrorMessage(reason), 'error'); await fetchComments() }
}
async function deleteComment(comment:CommentItem) {
  if (!await confirmAction(`Delete the comment from ${comment.author_name}?`)) return
  try { await api.delete(`/admin/comments/${comment.id}`); await Promise.all([fetchComments(), fetchStats()]); showToast('Comment deleted.') }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
async function bulkDelete() {
  const ids = [...selected.value]
  if (!ids.length || !await confirmAction(`Delete ${ids.length} selected comments?`)) return
  try { await Promise.all(ids.map(id => api.delete(`/admin/comments/${id}`))); await Promise.all([fetchComments(), fetchStats()]); showToast(`${ids.length} comments deleted.`) }
  catch (reason) { showToast(apiErrorMessage(reason), 'error'); await fetchComments() }
}
function badgeVariant(value:string) { return value === 'approved' ? 'success' : value === 'pending' ? 'warning' : 'danger' }
function formatDate(value:string) { return new Date(value).toLocaleString(undefined, { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' }) }
</script>
