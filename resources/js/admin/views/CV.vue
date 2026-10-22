<template>
  <Layout page-title="CV library">
    <div class="space-y-6">
      <PageHeader title="CV library" description="Manage the files available from your public portfolio.">
        <template #actions>
          <Button @click="openModal()">Add CV</Button>
        </template>
      </PageHeader>

      <ErrorBanner v-if="error" :message="error" :retry="fetchCvs" />
      <AdminLoader v-if="loading" title="Loading CVs" message="Fetching uploaded CV records." :rows="4" />
      <EmptyState v-else-if="cvs.length === 0" title="No CV files yet" description="Add a PDF or Word document to make it available to visitors." />

      <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="cv in cvs" :key="cv.id" class="flex flex-col gap-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h2 class="truncate text-lg font-semibold text-adm-ink">{{ cv.title }}</h2>
              <p class="mt-1 text-sm text-adm-ink-muted">Version {{ cv.version || '1.0' }}</p>
            </div>
            <Badge v-if="cv.is_active" variant="success">Active</Badge>
            <Badge v-else variant="neutral">Inactive</Badge>
          </div>
          <p class="min-h-10 text-sm leading-6 text-adm-ink-muted">{{ cv.description || 'No description provided.' }}</p>
          <dl class="grid grid-cols-2 gap-3 border-y border-adm-line py-3 text-sm">
            <div><dt class="text-adm-ink-muted">File</dt><dd class="mt-1 truncate font-medium text-adm-ink">{{ cv.file_name }}</dd></div>
            <div><dt class="text-adm-ink-muted">Size</dt><dd class="mt-1 font-medium text-adm-ink">{{ formatBytes(cv.file_size) }}</dd></div>
            <div><dt class="text-adm-ink-muted">Downloads</dt><dd class="mt-1 font-medium text-adm-ink">{{ cv.download_count || 0 }}</dd></div>
          </dl>
          <div class="mt-auto flex flex-wrap gap-2">
            <Button v-if="!cv.is_active" variant="secondary" size="sm" :disabled="workingId === cv.id" @click="setActive(cv)">Make active</Button>
            <Button variant="ghost" size="sm" @click="openModal(cv)">Edit</Button>
            <Button variant="danger" size="sm" @click="deleteCv(cv)">Delete</Button>
          </div>
        </Card>
      </div>

      <TransitionRoot :show="isOpen" as="template">
        <Dialog class="relative z-[var(--z-modal)]" @close="closeModal">
          <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-100" leave-from="opacity-100" leave-to="opacity-0">
            <div class="fixed inset-0 bg-black/50" />
          </TransitionChild>
          <div class="fixed inset-0 overflow-y-auto p-4">
            <div class="flex min-h-full items-center justify-center">
              <TransitionChild as="template" enter="ease-out duration-150" enter-from="opacity-0 translate-y-2" enter-to="opacity-100 translate-y-0" leave="ease-in duration-100" leave-from="opacity-100 translate-y-0" leave-to="opacity-0 translate-y-2">
                <DialogPanel class="w-full max-w-xl rounded-lg border border-adm-line bg-adm-surface p-5 shadow-adm-card sm:p-6">
                  <DialogTitle class="text-lg font-semibold text-adm-ink">{{ editingCv ? 'Edit CV' : 'Add CV' }}</DialogTitle>
                  <p class="mt-1 text-sm text-adm-ink-muted">PDF, DOC or DOCX up to 10 MB.</p>
                  <ErrorBanner v-if="formError" class="mt-4" :message="formError" />
                  <form class="mt-5 space-y-4" @submit.prevent="saveCv">
                    <div>
                      <label for="cv-title" class="mb-1 block text-sm font-medium text-adm-ink">Title</label>
                      <input id="cv-title" v-model="form.title" required maxlength="255" class="field-control" autocomplete="off" />
                    </div>
                    <div>
                      <label for="cv-version" class="mb-1 block text-sm font-medium text-adm-ink">Version</label>
                      <input id="cv-version" v-model="form.version" class="field-control" autocomplete="off" />
                    </div>
                    <div>
                      <label for="cv-description" class="mb-1 block text-sm font-medium text-adm-ink">Description</label>
                      <textarea id="cv-description" v-model="form.description" rows="3" class="field-control" />
                    </div>
                    <div>
                      <label for="cv-file" class="mb-1 block text-sm font-medium text-adm-ink">{{ editingCv ? 'Replace file (optional)' : 'File' }}</label>
                      <input id="cv-file" type="file" accept=".pdf,.doc,.docx,application/pdf" :required="!editingCv" class="field-control file:mr-3 file:rounded-md file:border-0 file:bg-adm-surface-2 file:px-3 file:py-2 file:text-sm file:font-medium file:text-adm-ink" @change="handleFile" />
                      <p v-if="editingCv" class="mt-1 text-xs text-adm-ink-muted">Current file: {{ editingCv.file_name }} ({{ formatBytes(editingCv.file_size) }})</p>
                    </div>
                    <label class="flex min-h-11 items-center gap-3 text-sm text-adm-ink">
                      <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-adm-line accent-adm-accent focus-visible:ring-2 focus-visible:ring-adm-focus" />
                      Set as active CV
                    </label>
                    <div class="flex justify-end gap-2 border-t border-adm-line pt-4">
                      <Button type="button" variant="secondary" @click="closeModal">Cancel</Button>
                      <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save CV' }}</Button>
                    </div>
                  </form>
                </DialogPanel>
              </TransitionChild>
            </div>
          </div>
        </Dialog>
      </TransitionRoot>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import Layout from '../components/Layout.vue'
import AdminLoader from '../components/AdminLoader.vue'
import api, { apiErrorMessage, fieldErrors } from '../utils/api'
import Button from '../ui/Button.vue'
import Badge from '../ui/Badge.vue'
import Card from '../ui/Card.vue'
import PageHeader from '../ui/PageHeader.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'
import { confirmAction, showToast } from '../ui/feedback'

interface CvRecord {
  id: number
  title: string
  version: string
  description?: string | null
  file_name: string
  file_size: number
  download_count: number
  is_active: boolean
}

const cvs = ref<CvRecord[]>([])
const isOpen = ref(false)
const editingCv = ref<CvRecord | null>(null)
const saving = ref(false)
const loading = ref(true)
const workingId = ref<number | null>(null)
const file = ref<File | null>(null)
const error = ref('')
const formError = ref('')
const form = ref({ title: '', version: '', description: '', is_active: false })

onMounted(fetchCvs)

async function fetchCvs() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/cv', { params: { per_page: 50 } })
    cvs.value = data.data
  } catch (reason) {
    error.value = apiErrorMessage(reason)
  } finally {
    loading.value = false
  }
}

function handleFile(event: Event) {
  const selected = (event.target as HTMLInputElement).files?.[0] || null
  if (selected && selected.size > 10 * 1024 * 1024) {
    formError.value = 'Choose a file smaller than 10 MB.'
    file.value = null
    ;(event.target as HTMLInputElement).value = ''
    return
  }
  file.value = selected
  formError.value = ''
}

function openModal(cv?: CvRecord) {
  editingCv.value = cv || null
  form.value = cv
    ? { title: cv.title, version: cv.version || '', description: cv.description || '', is_active: cv.is_active }
    : { title: '', version: '', description: '', is_active: false }
  file.value = null
  formError.value = ''
  isOpen.value = true
}

function closeModal() {
  isOpen.value = false
  file.value = null
  formError.value = ''
}

async function saveCv() {
  saving.value = true
  formError.value = ''
  const body = new FormData()
  body.append('title', form.value.title)
  body.append('version', form.value.version)
  body.append('description', form.value.description)
  body.append('is_active', String(form.value.is_active))
  if (file.value) body.append('file', file.value)
  if (!editingCv.value && !file.value) {
    formError.value = 'Select a file to upload.'
    saving.value = false
    return
  }
  try {
    if (editingCv.value) {
      await api.post(`/admin/cv/${editingCv.value.id}?_method=PUT`, body)
    } else {
      await api.post('/admin/cv', body)
    }
    await fetchCvs()
    closeModal()
    showToast('CV saved.')
  } catch (reason) {
    const errors = fieldErrors(reason)
    formError.value = Object.values(errors).flat().join(' ') || apiErrorMessage(reason)
  } finally {
    saving.value = false
  }
}

async function setActive(cv: CvRecord) {
  workingId.value = cv.id
  try {
    await api.post(`/admin/cv/${cv.id}/active`)
    await fetchCvs()
    showToast(`${cv.title} is now active.`)
  } catch (reason) {
    showToast(apiErrorMessage(reason), 'error')
  } finally {
    workingId.value = null
  }
}

async function deleteCv(cv: CvRecord) {
  if (!await confirmAction(`Delete “${cv.title}”? This also removes its stored file.`)) return
  try {
    await api.delete(`/admin/cv/${cv.id}`)
    await fetchCvs()
    showToast('CV deleted.')
  } catch (reason) {
    showToast(apiErrorMessage(reason), 'error')
  }
}

function formatBytes(bytes: number) {
  if (!bytes) return '—'
  const units = ['B', 'KB', 'MB', 'GB']
  let value = bytes
  let unit = 0
  while (value >= 1024 && unit < units.length - 1) { value /= 1024; unit++ }
  return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`
}
</script>
