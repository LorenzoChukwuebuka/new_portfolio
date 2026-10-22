<template>
  <Layout page-title="Tags">
    <div class="space-y-6">
      <PageHeader title="Tags" description="Use tags to connect related posts and projects."><template #actions><Button @click="openModal()">Add tag</Button></template></PageHeader>
      <ErrorBanner v-if="error" :message="error" :retry="fetchTags" />
      <AdminLoader v-if="loading && tags.length === 0" title="Loading tags" message="Fetching tag records." :rows="4" />
      <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <Card v-for="tag in tags" :key="tag.id" class="space-y-4 p-5">
          <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate text-lg font-semibold text-adm-ink">{{ tag.name }}</h2><p class="mt-1 text-sm text-adm-ink-muted">{{ tag.type || 'General' }}</p></div><Badge>{{ totalUsage(tag) }} uses</Badge></div>
          <div class="flex flex-wrap gap-2"><Badge variant="info">{{ tag.posts_count || 0 }} posts</Badge><Badge variant="neutral">{{ tag.projects_count || 0 }} projects</Badge></div>
          <div class="flex justify-end gap-2 border-t border-adm-line pt-3"><Button size="sm" variant="secondary" @click="openModal(tag)">Edit</Button><Button size="sm" variant="danger" :disabled="totalUsage(tag) > 0" :title="totalUsage(tag) ? 'Remove this tag from posts and projects before deleting' : 'Delete tag'" @click="deleteTag(tag)">Delete</Button></div>
          <p v-if="totalUsage(tag)" class="text-xs text-adm-ink-muted">Remove this tag from its {{ totalUsage(tag) }} assigned item{{ totalUsage(tag) === 1 ? '' : 's' }} before deleting it.</p>
        </Card>
        <EmptyState v-if="tags.length === 0 && !loading" title="No tags yet" description="Create tags to connect related content." />
      </div>
      <Modal :open="isOpen" :title="editing ? 'Edit tag' : 'Add tag'" @close="closeModal">
        <form class="space-y-4" @submit.prevent="saveTag">
          <p v-if="formError" role="alert" class="rounded-md border border-adm-danger-ink/30 bg-adm-danger-bg p-3 text-sm text-adm-danger-ink">{{ formError }}</p>
          <Field id="tag-name" label="Name" :error="errors.name" required><Input id="tag-name" v-model="form.name" required :error="errors.name" autocomplete="off" /></Field>
          <Field id="tag-type" label="Type" hint="Optional grouping label, such as technology or topic." :error="errors.type"><Input id="tag-type" v-model="form.type" :error="errors.type" autocomplete="off" /></Field>
          <div class="flex justify-end gap-2"><Button type="button" variant="secondary" @click="closeModal">Cancel</Button><Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save tag' }}</Button></div>
        </form>
      </Modal>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Layout from '../components/Layout.vue'
import AdminLoader from '../components/AdminLoader.vue'
import api, { apiErrorMessage, fieldErrors } from '../utils/api'
import { confirmAction, showToast } from '../ui/feedback'
import PageHeader from '../ui/PageHeader.vue'
import Button from '../ui/Button.vue'
import Card from '../ui/Card.vue'
import Badge from '../ui/Badge.vue'
import Field from '../ui/Field.vue'
import Input from '../ui/Input.vue'
import Modal from '../ui/Modal.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'

interface TagRecord { id?:number; name:string; type?:string; posts_count?:number; projects_count?:number }
const tags = ref<TagRecord[]>([])
const editing = ref<TagRecord|null>(null)
const isOpen = ref(false)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const errors = ref<Record<string,string>>({})
const form = ref<TagRecord>({ name:'', type:'' })
onMounted(fetchTags)

async function fetchTags() {
  loading.value = true; error.value = ''
  try { const { data } = await api.get('/admin/tags', { params:{ per_page:50 } }); tags.value = data.data }
  catch (reason) { error.value = apiErrorMessage(reason) }
  finally { loading.value = false }
}
function totalUsage(tag:TagRecord) { return (tag.posts_count || 0) + (tag.projects_count || 0) }
function openModal(tag?:TagRecord) {
  editing.value = tag || null
  form.value = tag ? { name:tag.name, type:tag.type || '' } : { name:'', type:'' }
  formError.value = ''; errors.value = {}; isOpen.value = true
}
function closeModal() { isOpen.value = false; editing.value = null; errors.value = {}; formError.value = '' }
async function saveTag() {
  saving.value = true; errors.value = {}; formError.value = ''
  try {
    if (editing.value?.id) await api.put(`/admin/tags/${editing.value.id}`, form.value)
    else await api.post('/admin/tags', form.value)
    await fetchTags(); closeModal(); showToast('Tag saved.')
  } catch (reason) {
    errors.value = fieldErrors(reason)
    formError.value = apiErrorMessage(reason)
  } finally { saving.value = false }
}
async function deleteTag(tag:TagRecord) {
  if (totalUsage(tag)) return
  if (!await confirmAction(`Delete “${tag.name}”?`)) return
  try { await api.delete(`/admin/tags/${tag.id}`); await fetchTags(); showToast('Tag deleted.') }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
</script>
