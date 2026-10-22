<template>
  <Layout page-title="Categories">
    <div class="space-y-6">
      <PageHeader title="Categories" description="Organize writeups into useful topic areas."><template #actions><Button @click="openModal()">Add category</Button></template></PageHeader>
      <ErrorBanner v-if="error" :message="error" :retry="fetchCategories" />
      <AdminLoader v-if="loading && categories.length === 0" title="Loading categories" message="Fetching category records." :rows="4" />
      <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="category in categories" :key="category.id" class="space-y-4 p-5">
          <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h2 class="truncate text-lg font-semibold text-adm-ink">{{ category.name }}</h2><p class="mt-1 truncate font-mono text-xs text-adm-ink-muted">/{{ category.slug }}</p></div><Badge>{{ category.posts_count }} posts</Badge></div>
          <p class="min-h-10 text-sm leading-6 text-adm-ink-muted">{{ category.description || 'No description.' }}</p>
          <div class="flex justify-end gap-2 border-t border-adm-line pt-3"><Button size="sm" variant="secondary" @click="openModal(category)">Edit</Button><Button size="sm" variant="danger" :disabled="Boolean(category.posts_count)" :title="category.posts_count ? `In use by ${category.posts_count} posts` : 'Delete category'" @click="deleteCategory(category)">Delete</Button></div>
          <p v-if="category.posts_count" class="text-xs text-adm-ink-muted">This category is assigned to {{ category.posts_count }} post{{ category.posts_count === 1 ? '' : 's' }} and cannot be deleted.</p>
        </Card>
        <EmptyState v-if="categories.length === 0 && !loading" title="No categories yet" description="Create a category to organize your blog." />
      </div>

      <Modal :open="isOpen" :title="editing ? 'Edit category' : 'Add category'" @close="closeModal">
        <form class="space-y-4" @submit.prevent="saveCategory">
          <p v-if="formError" role="alert" class="rounded-md border border-adm-danger-ink/30 bg-adm-danger-bg p-3 text-sm text-adm-danger-ink">{{ formError }}</p>
          <Field id="category-name" label="Name" :error="errors.name" required><Input id="category-name" v-model="form.name" required :error="errors.name" autocomplete="off" /></Field>
          <Field id="category-slug" label="Slug" hint="Leave blank to create it from the name." :error="errors.slug"><Input id="category-slug" v-model="form.slug" :error="errors.slug" autocomplete="off" /></Field>
          <Field id="category-description" label="Description" :error="errors.description"><Textarea id="category-description" v-model="form.description" :error="errors.description" :rows="3" /></Field>
          <div class="flex justify-end gap-2"><Button type="button" variant="secondary" @click="closeModal">Cancel</Button><Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save category' }}</Button></div>
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
import Textarea from '../ui/Textarea.vue'
import Modal from '../ui/Modal.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'

interface CategoryRecord { id?:number; name:string; slug?:string; description?:string; posts_count?:number }
const categories = ref<CategoryRecord[]>([])
const editing = ref<CategoryRecord|null>(null)
const isOpen = ref(false)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const formError = ref('')
const errors = ref<Record<string,string>>({})
const form = ref<CategoryRecord>({ name:'', slug:'', description:'' })
onMounted(fetchCategories)

async function fetchCategories() {
  loading.value = true; error.value = ''
  try { const { data } = await api.get('/admin/categories', { params:{ per_page:50 } }); categories.value = data.data }
  catch (reason) { error.value = apiErrorMessage(reason) }
  finally { loading.value = false }
}
function openModal(category?:CategoryRecord) {
  editing.value = category || null
  form.value = category ? { name:category.name, slug:category.slug || '', description:category.description || '' } : { name:'', slug:'', description:'' }
  formError.value = ''; errors.value = {}; isOpen.value = true
}
function closeModal() { isOpen.value = false; editing.value = null; errors.value = {}; formError.value = '' }
async function saveCategory() {
  saving.value = true; errors.value = {}; formError.value = ''
  try {
    if (editing.value?.id) await api.put(`/admin/categories/${editing.value.id}`, form.value)
    else await api.post('/admin/categories', form.value)
    await fetchCategories(); closeModal(); showToast('Category saved.')
  } catch (reason) {
    errors.value = fieldErrors(reason)
    formError.value = apiErrorMessage(reason)
  } finally { saving.value = false }
}
async function deleteCategory(category:CategoryRecord) {
  if ((category.posts_count || 0) > 0) return
  if (!await confirmAction(`Delete “${category.name}”?`)) return
  try { await api.delete(`/admin/categories/${category.id}`); await fetchCategories(); showToast('Category deleted.') }
  catch (reason) { showToast(apiErrorMessage(reason), 'error') }
}
</script>
