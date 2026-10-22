<template>
  <Layout :page-title="isEditing ? 'Edit project' : 'New project'">
    <div class="admin-page pb-24 lg:pb-0">
      <PageHeader :title="isEditing ? 'Edit project' : 'New project'" description="Build a useful case study with clear details and optional public links." breadcrumb="Content / Projects"/>
      <ErrorBanner v-if="failure" class="mb-4" :message="failure"/>
      <form class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_19rem]" @submit.prevent="saveProject">
        <div class="grid gap-5">
          <Card><h2 class="mb-4 text-base font-semibold">Project details</h2><div class="grid gap-4">
            <Field id="project-title" label="Title" required :error="errors.title"><Input id="project-title" v-model="form.title" :error="errors.title" autocomplete="off"/></Field>
            <Field id="project-slug" label="Slug" hint="Leave blank to generate it from the title."><Input id="project-slug" v-model="form.slug" placeholder="generated-from-title"/></Field>
            <Field id="project-description" label="Short description" required :error="errors.description"><Textarea id="project-description" v-model="form.description" :error="errors.description" :rows="3" placeholder="A concise summary for the project card and search preview."/></Field>
            <Field id="project-long-description" label="Full description"><div class="mb-2 flex gap-2"><button class="rounded-md border border-adm-line px-3 py-2 text-xs font-semibold" :class="preview?'text-adm-ink-muted':'bg-adm-surface-2 text-adm-ink'" type="button" @click="preview=false">Write</button><button class="rounded-md border border-adm-line px-3 py-2 text-xs font-semibold" :class="preview?'bg-adm-surface-2 text-adm-ink':'text-adm-ink-muted'" type="button" @click="preview=true">Preview</button></div><Textarea v-if="!preview" id="project-long-description" v-model="form.full_description" :rows="10" placeholder="Use blank lines for paragraphs, ## for headings, and - for list items."/><div v-else class="min-h-48 rounded-md border border-adm-line bg-adm-surface-2 p-4"><template v-for="(block,index) in previewBlocks" :key="index"><h3 v-if="block.type==='heading'" class="mb-2 mt-5 text-lg font-semibold">{{ block.text }}</h3><ul v-else-if="block.type==='list'" class="my-3 list-disc space-y-1 pl-6"><li v-for="(item,itemIndex) in block.items" :key="itemIndex">{{ item }}</li></ul><p v-else class="my-3 whitespace-pre-wrap text-sm leading-6 text-adm-ink-muted">{{ block.text }}</p></template><p v-if="!previewBlocks.length" class="text-sm text-adm-ink-muted">Nothing to preview yet.</p></div></Field>
          </div></Card>
          <Card><h2 class="mb-4 text-base font-semibold">Links</h2><div class="grid gap-4 sm:grid-cols-2"><Field id="project-link" label="Public project link" hint="Optional for private client work." :error="errors.project_link"><Input id="project-link" v-model="form.project_link" type="url" :error="errors.project_link" placeholder="https://example.com"/></Field><Field id="project-github" label="Source code link" :error="errors.github_link"><Input id="project-github" v-model="form.github_link" type="url" :error="errors.github_link" placeholder="https://github.com/…"/></Field></div></Card>
          <Card><h2 class="mb-4 text-base font-semibold">Technology</h2><Field id="project-tech" label="Technologies"><TagInput v-model="form.technologies" :options="techSuggestions" placeholder="Add a technology and press Enter"/></Field><div v-if="tagOptions.length" class="mt-4"><p class="mb-2 text-sm font-semibold">Related tags</p><MultiSelectChips v-model="form.tags" :options="tagOptions"/></div></Card>
        </div>
        <aside class="grid gap-5 xl:sticky xl:top-24">
          <Card><div class="mb-4 flex items-center justify-between"><h2 class="text-base font-semibold">Publish settings</h2><Badge :variant="form.status==='completed'?'success':form.status==='archived'?'neutral':'warning'">{{ form.status.replace('-', ' ') }}</Badge></div><div class="grid gap-4"><Field id="project-status" label="Status"><Select id="project-status" v-model="form.status"><option value="in-progress">In progress</option><option value="completed">Completed</option><option value="archived">Archived</option></Select></Field><Field id="project-date" label="Completed on"><Input id="project-date" v-model="form.completed_at" type="date"/></Field><Field id="project-order" label="Display order"><Input id="project-order" v-model.number="form.order" type="number" min="0"/></Field><Switch id="project-featured" v-model="form.is_featured">Feature this project</Switch></div><div class="mt-5 grid gap-2"><Button type="submit" :loading="saving">{{ isEditing?'Save changes':'Create project' }}</Button><router-link to="/admin/projects" class="flex min-h-10 items-center justify-center rounded-md border border-adm-line text-sm font-semibold hover:bg-adm-surface-2">Cancel</router-link></div></Card>
          <Card><h2 class="mb-4 text-base font-semibold">Images</h2><Field id="project-thumb" label="Project thumbnail"><ImageUpload :existing="thumbnailUrl" @update:file="form.thumbnail=$event" @remove-existing="removeThumbnail=$event"/></Field><Field id="project-image" class="mt-4" label="Main project image"><ImageUpload :existing="mainImageUrl" @update:file="form.main_image=$event" @remove-existing="removeMainImage=$event"/></Field></Card>
        </aside>
        <div class="fixed inset-x-0 bottom-0 z-20 flex gap-2 border-t border-adm-line bg-adm-surface/95 p-3 backdrop-blur xl:hidden"><router-link to="/admin/projects" class="flex min-h-11 flex-1 items-center justify-center rounded-md border border-adm-line text-sm">Cancel</router-link><Button type="submit" class="flex-1" :loading="saving">{{ isEditing?'Save changes':'Create project' }}</Button></div>
      </form>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { apiErrorMessage, fieldErrors } from '../utils/api'
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
import Switch from '../ui/Switch.vue'
import TagInput from '../ui/TagInput.vue'
import Textarea from '../ui/Textarea.vue'
import { useUnsavedGuard } from '../ui/useUnsavedGuard'
import { useToast } from '../ui/useToast'

interface ProjectFormData { title:string;slug:string;description:string;full_description:string;project_link:string;github_link:string;technologies:string[];status:'completed'|'in-progress'|'archived';completed_at:string;order:number;is_featured:boolean;tags:number[];thumbnail:File|null;main_image:File|null }
interface TagOption {id:number;name:string}
const route=useRoute();const router=useRouter();const toast=useToast();const isEditing=ref(false);const saving=ref(false);const preview=ref(false);const failure=ref('');const errors=ref<Record<string,string>>({});const tagOptions=ref<TagOption[]>([]);const techSuggestions=ref<string[]>([]);const thumbnailUrl=ref('');const mainImageUrl=ref('');const removeThumbnail=ref(false);const removeMainImage=ref(false)
const form=ref<ProjectFormData>({title:'',slug:'',description:'',full_description:'',project_link:'',github_link:'',technologies:[],status:'in-progress',completed_at:'',order:0,is_featured:false,tags:[],thumbnail:null,main_image:null})
useUnsavedGuard(form)
const previewBlocks=computed(()=>{const result:{type:'paragraph'|'heading'|'list';text?:string;items?:string[]}[]=[];const lines=form.value.full_description.split(/\r?\n/);let paragraph:string[]=[];let list:string[]=[];const flushParagraph=()=>{if(paragraph.length){result.push({type:'paragraph',text:paragraph.join(' ')});paragraph=[]}};const flushList=()=>{if(list.length){flushParagraph();result.push({type:'list',items:[...list]});list=[]}};for(const line of lines){if(line.startsWith('## ')){flushList();flushParagraph();result.push({type:'heading',text:line.slice(3).trim()})}else if(line.startsWith('- ')){flushParagraph();list.push(line.slice(2).trim())}else if(!line.trim()){flushList();flushParagraph()}else{flushList();paragraph.push(line.trim())}}flushList();flushParagraph();return result})
function storageUrl(path?:string|null){if(!path)return '';return path.startsWith('http')||path.startsWith('/storage/')?path:`/storage/${path.replace(/^\//,'')}`}
function dateValue(value?:string|null){if(!value)return '';return value.slice(0,10)}
onMounted(async()=>{try{const [tagsResponse,projectsResponse]=await Promise.all([api.get('/admin/tags',{params:{per_page:50}}),api.get('/admin/projects',{params:{per_page:50}})]);tagOptions.value=(tagsResponse.data.data||[]).map((tag:TagOption)=>({id:tag.id,name:tag.name}));const suggestions=(projectsResponse.data.data||[]).flatMap((project:{technologies?:string[]})=>project.technologies||[]) as string[];techSuggestions.value=Array.from(new Set<string>(suggestions));const slug=route.params.id;if(slug){isEditing.value=true;const {data}=await api.get(`/admin/projects/${slug}`);form.value={title:data.title||'',slug:data.slug||'',description:data.description||'',full_description:data.full_description||'',project_link:data.project_link||'',github_link:data.github_link||'',technologies:Array.isArray(data.technologies)?data.technologies:[],status:data.status||'in-progress',completed_at:dateValue(data.completed_at),order:Number(data.order||0),is_featured:Boolean(data.is_featured),tags:(data.tags||[]).map((tag:TagOption|number)=>typeof tag==='number'?tag:tag.id),thumbnail:null,main_image:null};thumbnailUrl.value=storageUrl(data.media?.find((media:{type:string})=>media.type==='thumbnail')?.url);mainImageUrl.value=storageUrl(data.media?.find((media:{type:string})=>media.type==='image')?.url)}}catch(error){failure.value=apiErrorMessage(error,'Project details could not be loaded.')}})
function payload(){const result=new FormData();const fields:Record<string,string>={title:form.value.title,slug:form.value.slug,description:form.value.description,full_description:form.value.full_description,project_link:form.value.project_link,github_link:form.value.github_link,technologies:JSON.stringify(form.value.technologies),status:form.value.status,completed_at:form.value.completed_at,order:String(form.value.order),is_featured:String(form.value.is_featured),tags:JSON.stringify(form.value.tags)};for(const [key,value] of Object.entries(fields))result.append(key,value);if(form.value.thumbnail)result.append('thumbnail',form.value.thumbnail);if(form.value.main_image)result.append('main_image',form.value.main_image);if(removeThumbnail.value)result.append('remove_thumbnail','1');if(removeMainImage.value)result.append('remove_main_image','1');return result}
async function saveProject(){saving.value=true;failure.value='';errors.value={};try{const data=payload();if(isEditing.value){data.append('_method','put');await api.post(`/admin/projects/${route.params.id}`,data,{headers:{'Content-Type':'multipart/form-data'}})}else await api.post('/admin/projects',data,{headers:{'Content-Type':'multipart/form-data'}});toast(isEditing.value?'Project updated.':'Project created.');await router.push('/admin/projects')}catch(error){errors.value=fieldErrors(error);failure.value=apiErrorMessage(error,'Project could not be saved.');toast(failure.value,'error')}finally{saving.value=false}}
</script>
