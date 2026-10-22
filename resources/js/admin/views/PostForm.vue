<template>
  <Layout :page-title="isEditing ? 'Edit post' : 'New post'">
    <div class="admin-page pb-24 lg:pb-0">
      <PageHeader :title="isEditing ? 'Edit post' : 'New post'" description="Write, refine, and publish a clear portfolio article." breadcrumb="Content / Posts">
        <template #actions><Button variant="secondary" @click="savePost(false)">Save draft</Button><Button @click="savePost(true)">{{ form.status==='published'?'Update post':'Publish' }}</Button></template>
      </PageHeader>
      <div v-if="hasDraft" class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md border border-adm-warning-ink/25 bg-adm-warning-bg p-4 text-sm text-adm-warning-ink"><p>A local draft was saved {{ draftTimestamp }}.</p><div class="flex gap-2"><Button variant="secondary" @click="restoreDraft">Restore draft</Button><Button variant="ghost" @click="clearDraft">Dismiss</Button></div></div>
      <ErrorBanner v-if="failure" class="mb-4" :message="failure"/>
      <form id="post-form" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent="savePost()">
        <div class="grid min-w-0 gap-5">
          <Card><div class="grid gap-4"><Field id="post-title" label="Title" required :error="errors.title"><Input id="post-title" v-model="form.title" :error="errors.title" placeholder="A clear, specific headline" autocomplete="off"/></Field><Field id="post-slug" label="URL slug" hint="Edit manually or use Reset to follow the title."><div class="flex gap-2"><Input id="post-slug" v-model="form.slug" placeholder="generated-from-title" @update:model-value="slugWasEdited=true"/><Button variant="secondary" @click="resetSlug">Reset</Button></div></Field><Field id="post-excerpt" label="Excerpt" required :error="errors.excerpt"><Textarea id="post-excerpt" v-model="form.excerpt" :error="errors.excerpt" :rows="3" placeholder="Summarize the writeup in a couple of sentences."/><p class="text-right text-xs text-adm-ink-muted">{{ form.excerpt.length }} characters</p></Field></div></Card>
          <Card><div class="mb-3 flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-semibold">Writeup</h2><p class="text-xs text-adm-ink-muted">Headings · lists · links · code · images</p></div><Field id="post-content" label="Article body" required :error="errors.content"><div ref="editorContainer" class="admin-quill-editor"/><p v-if="errors.content" class="mt-2 text-sm text-adm-danger-ink">{{ errors.content }}</p></Field></Card>
          <Card><h2 class="mb-4 text-base font-semibold">Metadata</h2><div class="grid gap-3"><div v-for="row in metaRows" :key="row.id" class="grid gap-2 sm:grid-cols-[1fr_1.3fr_auto]"><Field :id="`meta-key-${row.id}`" label="Key"><Input :id="`meta-key-${row.id}`" v-model="row.key" placeholder="og:title" @blur="syncMeta"/></Field><Field :id="`meta-value-${row.id}`" label="Value"><Input :id="`meta-value-${row.id}`" v-model="row.value" placeholder="Optional value" @blur="syncMeta"/></Field><Button class="self-end" variant="danger" @click="removeMeta(row.id)">Remove</Button></div><Button class="justify-self-start" variant="secondary" @click="addMeta">Add metadata field</Button></div></Card>
        </div>

        <aside class="grid gap-5 xl:sticky xl:top-24">
          <Card><h2 class="mb-4 text-base font-semibold">Publishing</h2><div class="grid gap-4"><Field id="post-status" label="Status"><Select id="post-status" v-model="form.status"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></Select></Field><Field id="post-published-at" label="Publish date"><Input id="post-published-at" v-model="form.published_at" type="datetime-local"/></Field><Field id="post-category" label="Category"><Select id="post-category" v-model="form.category_id"><option :value="undefined">Uncategorized</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></Select></Field><Field id="post-author" label="Author"><Input id="post-author" v-model="form.author" autocomplete="name"/></Field><Field id="post-read-time" label="Read time"><div class="flex gap-2"><Input id="post-read-time" v-model.number="form.read_time" type="number" min="1"/><Button variant="secondary" @click="estimateReadTime">Estimate</Button></div></Field></div></Card>
          <Card><h2 class="mb-3 text-base font-semibold">Tags</h2><MultiSelectChips v-model="form.tags" :options="tags"/></Card>
          <Card><h2 class="mb-3 text-base font-semibold">Featured image</h2><ImageUpload :existing="featuredImageUrl" @update:file="form.featured_image=$event" @remove-existing="removeFeaturedImage=$event"/><p v-if="form.featured_image" class="mt-2 truncate text-xs text-adm-ink-muted">Selected: {{ form.featured_image.name }}</p></Card>
          <Card><p class="eyebrow text-adm-ink-muted">Search preview</p><div class="mt-2 grid gap-1"><p class="truncate text-sm font-semibold text-adm-info-ink">{{ seoTitle || 'Your article title' }}</p><p class="truncate text-xs text-adm-success-ink">{{ seoUrl }}</p><p class="line-clamp-2 text-xs text-adm-ink-muted">{{ seoDescription || 'Your excerpt will appear in search results.' }}</p></div></Card>
        </aside>
      </form>
      <Teleport to="body"><div class="fixed inset-x-0 bottom-0 z-[var(--z-action-bar)] flex gap-2 border-t border-adm-line bg-adm-surface/95 p-3 backdrop-blur xl:hidden"><Button class="flex-1" variant="secondary" @click="savePost(false)">Save draft</Button><Button class="flex-1" @click="savePost(true)">{{ form.status==='published'?'Update':'Publish' }}</Button></div></Teleport>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'
import api, { apiErrorMessage, fieldErrors } from '../utils/api'
import type { Category } from '../types'
import Layout from '../components/Layout.vue'
import Badge from '../ui/Badge.vue'
import Button from '../ui/Button.vue'
import Card from '../ui/Card.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'
import Field from '../ui/Field.vue'
import ImageUpload from '../ui/ImageUpload.vue'
import Input from '../ui/Input.vue'
import MultiSelectChips from '../ui/MultiSelectChips.vue'
import PageHeader from '../ui/PageHeader.vue'
import Select from '../ui/Select.vue'
import Textarea from '../ui/Textarea.vue'
import { useUnsavedGuard } from '../ui/useUnsavedGuard'
import { useToast } from '../ui/useToast'

interface TagOption {id:number;name:string}
interface MetaRow {id:string;key:string;value:string}
interface PostFormData {category_id?:number;title:string;slug:string;excerpt:string;content:string;author:string;read_time?:number;status:'draft'|'published'|'archived';published_at:string;meta_data:Record<string,string>;tags:number[];featured_image:File|null}
const route=useRoute();const router=useRouter();const toast=useToast();const editorContainer=ref<HTMLElement|null>(null);const categories=ref<Category[]>([]);const tags=ref<TagOption[]>([]);const metaRows=ref<MetaRow[]>([]);const hasDraft=ref(false);const draftTimestamp=ref('');const featuredImageUrl=ref('');const removeFeaturedImage=ref(false);const errors=ref<Record<string,string>>({});const failure=ref('');const saving=ref(false);const isEditing=ref(false);const slugWasEdited=ref(false);let editor:Quill|null=null;let draftTimer:ReturnType<typeof setTimeout>|undefined;let draftKey='post_form_draft_create'
const form=ref<PostFormData>({title:'',slug:'',excerpt:'',content:'',author:'Lorenzo Chukwuebuka Obi',status:'draft',published_at:'',meta_data:{},tags:[],featured_image:null})
const {reset:resetGuard}=useUnsavedGuard(form)
const seoTitle=computed(()=>form.value.title.trim());const seoDescription=computed(()=>form.value.excerpt.trim());const seoUrl=computed(()=>`${window.location.origin}/blog/${form.value.slug||'article-slug'}`)
watch(()=>form.value.title,(title)=>{if(!slugWasEdited.value)form.value.slug=slugify(title)})
watch([form,metaRows],()=>scheduleDraft(),{deep:true})
function slugify(value:string){return value.toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')}
function resetSlug(){slugWasEdited.value=false;form.value.slug=slugify(form.value.title)}
function localDateTime(value?:string|null){if(!value)return '';const date=new Date(value);if(Number.isNaN(date.getTime()))return '';date.setMinutes(date.getMinutes()-date.getTimezoneOffset());return date.toISOString().slice(0,16)}
function isoDateTime(value:string){return value?new Date(value).toISOString():''}
function imageUrl(path?:string|null){if(!path)return '';return path.startsWith('http')||path.startsWith('/storage/')?path:`/storage/${path.replace(/^\//,'')}`}
function syncMeta(){const next:Record<string,string>={};metaRows.value.forEach(row=>{const key=row.key.trim();if(key)next[key]=row.value});form.value.meta_data=next}
function addMeta(){metaRows.value.push({id:crypto.randomUUID(),key:'',value:''})}
function removeMeta(id:string){metaRows.value=metaRows.value.filter(row=>row.id!==id);syncMeta()}
function estimateReadTime(){const text=(editor?.getText()||'').trim();form.value.read_time=Math.max(1,Math.ceil((text.match(/[\p{L}\p{N}]+/gu)||[]).length/200))}
function saveDraftSnapshot(){const {featured_image,...safeForm}=form.value;localStorage.setItem(draftKey,JSON.stringify({form:safeForm,metaRows:metaRows.value,featuredImageUrl:featuredImageUrl.value,updatedAt:new Date().toISOString()}));if(editor)localStorage.setItem(`${draftKey}:content`,editor.root.innerHTML)}
function scheduleDraft(){clearTimeout(draftTimer);draftTimer=window.setTimeout(()=>{try{saveDraftSnapshot()}catch{/* localStorage quota or privacy mode */}},800)}
function restoreDraft(){try{const saved=JSON.parse(localStorage.getItem(draftKey)||'{}');if(!saved.form)return;form.value={...form.value,...saved.form,featured_image:null};metaRows.value=saved.metaRows||Object.entries(saved.form.meta_data||{}).map(([key,value])=>({id:crypto.randomUUID(),key,value:String(value)}));featuredImageUrl.value=saved.featuredImageUrl||featuredImageUrl.value;if(editor)editor.root.innerHTML=localStorage.getItem(`${draftKey}:content`)||saved.form.content||'';hasDraft.value=false;toast('Local draft restored.','info')}catch{toast('Saved draft could not be restored.','error')}}
function clearDraft(){localStorage.removeItem(draftKey);localStorage.removeItem(`${draftKey}:content`);hasDraft.value=false}
function setupImageHandler(instance:Quill){const toolbar=instance.getModule('toolbar') as {addHandler:(name:string,handler:()=>void)=>void};toolbar.addHandler('image',()=>{const input=document.createElement('input');input.type='file';input.accept='image/jpeg,image/png,image/webp,image/gif';input.onchange=async()=>{const file=input.files?.[0];if(!file)return;if(file.size>5*1024*1024){toast('Images must be 5 MB or smaller.','error');return}const selection=instance.getSelection(true);if(!selection)return;const data=new FormData();data.append('image',file);try{const response=await api.uploadFile('/admin/uploads',data);instance.insertEmbed(selection.index,'image',response.data.url,'user');instance.setSelection(selection.index+1);toast('Image uploaded.','success')}catch(error){toast(apiErrorMessage(error,'Image upload failed.'),'error')}};input.click()})}
onMounted(async()=>{
  try{
    const slug=route.params.slug
    isEditing.value=Boolean(slug)
    if(slug)draftKey=`post_form_draft_${String(slug)}`
    const [categoryResponse,tagResponse]=await Promise.all([api.get('/admin/categories',{params:{per_page:50}}),api.get('/admin/tags',{params:{per_page:50}})])
    categories.value=categoryResponse.data.data;tags.value=tagResponse.data.data
    if(slug){const {data}=await api.get(`/admin/posts/${slug}`);form.value={category_id:data.category_id||undefined,title:data.title||'',slug:data.slug||'',excerpt:data.excerpt||'',content:data.content||'',author:data.author||'',read_time:data.read_time||undefined,status:data.status||'draft',published_at:localDateTime(data.published_at),meta_data:data.meta_data||{},tags:(data.tags||[]).map((tag:TagOption)=>tag.id),featured_image:null};featuredImageUrl.value=imageUrl(data.media?.find((media:{type:string})=>media.type==='featured')?.url);slugWasEdited.value=true;metaRows.value=Object.entries(form.value.meta_data).map(([key,value])=>({id:crypto.randomUUID(),key,value:String(value)}))}
    await nextTick()
    if (editorContainer.value) {
      editor = new Quill(editorContainer.value, {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ header: [2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ indent: '-1' }, { indent: '+1' }],
            [{ align: [] }],
            ['link', 'image'],
            ['clean'],
          ],
        },
        placeholder: 'Write the article...',
      })
      setupImageHandler(editor)
      editor.root.innerHTML = form.value.content || ''
      editor.on('text-change', () => {
        form.value.content = editor?.root.innerHTML || ''
        scheduleDraft()
      })
    }
    const previous=localStorage.getItem(draftKey);if(previous){try{const saved=JSON.parse(previous);hasDraft.value=true;draftTimestamp.value=new Date(saved.updatedAt).toLocaleString()}catch{clearDraft()}}
    resetGuard()
    window.addEventListener('keydown',saveShortcut)
  }catch(error){failure.value=apiErrorMessage(error,'Post editor could not be loaded.')}
})
onBeforeUnmount(()=>{clearTimeout(draftTimer);window.removeEventListener('keydown',saveShortcut);if(editor){editor.off('text-change');editor=null}})
function saveShortcut(event:KeyboardEvent){if((event.metaKey||event.ctrlKey)&&event.key.toLowerCase()==='s'){event.preventDefault();void savePost()}}
async function savePost(publish?:boolean){if(!editor)return;failure.value='';errors.value={};saving.value=true;form.value.content=editor.root.innerHTML;if(publish!==undefined)form.value.status=publish?'published':'draft';syncMeta();const data=new FormData();const fields:Record<string,string>={category_id:form.value.category_id?String(form.value.category_id):'',title:form.value.title,slug:form.value.slug,excerpt:form.value.excerpt,content:form.value.content,author:form.value.author,read_time:form.value.read_time?String(form.value.read_time):'',status:form.value.status,published_at:isoDateTime(form.value.published_at),meta_data:JSON.stringify(form.value.meta_data),tags:JSON.stringify(form.value.tags)};Object.entries(fields).forEach(([key,value])=>data.append(key,value));if(form.value.featured_image)data.append('featured_image',form.value.featured_image);if(removeFeaturedImage.value)data.append('remove_featured_image','1');try{if(isEditing.value){data.append('_method','put');await api.post(`/admin/posts/${route.params.slug}`,data,{headers:{'Content-Type':'multipart/form-data'}})}else await api.post('/admin/posts',data,{headers:{'Content-Type':'multipart/form-data'}});clearDraft();resetGuard();toast(form.value.status==='published'?'Post published.':'Draft saved.','success');await router.push('/admin/posts')}catch(error){errors.value=fieldErrors(error);failure.value=apiErrorMessage(error,'Post could not be saved.');toast(failure.value,'error')}finally{saving.value=false}}
</script>

<style>
.admin-quill-editor .ql-toolbar.ql-snow,.admin-quill-editor .ql-container.ql-snow{border-color:var(--adm-line);color:var(--adm-ink)}
.admin-quill-editor .ql-toolbar.ql-snow{border-radius:var(--radius-md) var(--radius-md) 0 0;background:var(--adm-surface-2)}
.admin-quill-editor .ql-container.ql-snow{border-radius:0 0 var(--radius-md) var(--radius-md);background:var(--adm-surface);font:400 1rem/1.7 var(--font-body)}
.admin-quill-editor .ql-editor{min-height:22rem;color:var(--adm-ink)}
.admin-quill-editor .ql-editor.ql-blank::before{color:var(--adm-ink-muted)}
.admin-quill-editor .ql-stroke{stroke:var(--adm-ink-muted)}
.admin-quill-editor .ql-fill{fill:var(--adm-ink-muted)}
.admin-quill-editor .ql-picker{color:var(--adm-ink-muted)}
</style>
