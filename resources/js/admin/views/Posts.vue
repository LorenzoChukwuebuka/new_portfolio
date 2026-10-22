<template>
  <Layout page-title="Posts">
    <div class="admin-page">
      <PageHeader title="Posts" description="Draft, publish, and manage the articles in your portfolio.">
        <template #actions><router-link to="/admin/posts/create" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-adm-accent-bg px-4 py-2 text-sm font-semibold text-adm-accent-ink"><Icon name="plus" class-name="h-4 w-4"/>New post</router-link></template>
      </PageHeader>
      <Card>
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem]">
          <SearchInput v-model="search" label="Search posts" placeholder="Search title, excerpt, or content…"/>
          <label class="sr-only" for="post-status">Filter posts by status</label><select id="post-status" v-model="filter" class="min-h-11 rounded-md border border-adm-line bg-adm-surface px-3 text-sm text-adm-ink" @change="page=1;fetchPosts()"><option value="">All posts</option><option value="published">Published</option><option value="draft">Draft</option><option value="archived">Archived</option></select>
        </div>
        <p v-if="loading && posts.length" class="mt-3 text-xs text-adm-ink-muted" role="status">Updating results…</p>
        <ErrorBanner v-if="error" class="mt-4" :message="error" :retry="fetchPosts"/>
        <div class="mt-4">
          <DataTable :columns="columns" :rows="posts">
            <template #row="{row}">
              <td class="max-w-[34rem] px-4 py-4"><router-link class="font-semibold text-adm-ink hover:text-adm-accent" :to="`/admin/posts/${row.slug}/edit`">{{ row.title }}</router-link><p class="mt-1 truncate text-xs text-adm-ink-muted">{{ row.excerpt }}</p></td>
              <td class="px-4 py-4 text-adm-ink-muted">{{ row.category?.name || 'Uncategorized' }}</td>
              <td class="px-4 py-4"><Badge :variant="statusVariant(row.status)">{{ row.status }}</Badge></td>
              <td class="px-4 py-4 text-adm-ink-muted">{{ row.views_count || 0 }}</td>
              <td class="px-4 py-4 text-adm-ink-muted">{{ formatDate(row.published_at || row.created_at) }}</td>
              <td class="px-4 py-4 text-right"><div class="flex justify-end gap-3"><a v-if="row.status==='published'" class="text-sm text-adm-ink-muted underline" :href="`/blog/${row.slug}`" target="_blank" rel="noopener noreferrer">View</a><router-link class="text-sm font-semibold text-adm-accent" :to="`/admin/posts/${row.slug}/edit`">Edit</router-link><details class="relative text-left"><summary class="min-h-9 cursor-pointer rounded px-2 py-1 text-sm text-adm-ink-muted hover:bg-adm-surface-2">More</summary><div class="absolute right-0 z-20 mt-1 grid min-w-40 rounded-md border border-adm-line bg-adm-surface p-1 shadow-adm-card"><button v-if="row.status!=='published'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'published')">Publish</button><button v-if="row.status!=='draft'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'draft')">Move to draft</button><button v-if="row.status!=='archived'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'archived')">Archive</button><button class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="duplicatePost(row)">Duplicate</button><button class="rounded px-3 py-2 text-left text-sm text-adm-danger-ink hover:bg-adm-danger-bg" @click="deletePost(row)">Delete</button></div></details></div></td>
            </template>
            <template #mobile="{row}">
              <div class="flex items-start justify-between gap-3"><div class="min-w-0"><router-link class="font-semibold text-adm-ink" :to="`/admin/posts/${row.slug}/edit`">{{ row.title }}</router-link><p class="mt-1 text-xs text-adm-ink-muted">{{ row.category?.name || 'Uncategorized' }} · {{ formatDate(row.published_at || row.created_at) }}</p></div><Badge :variant="statusVariant(row.status)">{{ row.status }}</Badge></div>
              <p class="mt-2 line-clamp-2 text-sm text-adm-ink-muted">{{ row.excerpt }}</p><div class="mt-3 flex flex-wrap items-center justify-between gap-3"><span class="text-xs text-adm-ink-muted">{{ row.views_count || 0 }} views</span><div class="flex flex-wrap gap-3"><a v-if="row.status==='published'" class="text-sm underline" :href="`/blog/${row.slug}`" target="_blank" rel="noopener noreferrer">View</a><router-link class="text-sm font-semibold text-adm-accent" :to="`/admin/posts/${row.slug}/edit`">Edit</router-link><details class="relative"><summary class="min-h-9 cursor-pointer rounded px-2 py-1 text-sm text-adm-ink-muted">More</summary><div class="absolute right-0 z-20 mt-1 grid min-w-40 rounded-md border border-adm-line bg-adm-surface p-1 shadow-adm-card"><button v-if="row.status!=='published'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'published')">Publish</button><button v-if="row.status!=='draft'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'draft')">Move to draft</button><button v-if="row.status!=='archived'" class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="changeStatus(row,'archived')">Archive</button><button class="rounded px-3 py-2 text-left text-sm hover:bg-adm-surface-2" @click="duplicatePost(row)">Duplicate</button><button class="rounded px-3 py-2 text-left text-sm text-adm-danger-ink hover:bg-adm-danger-bg" @click="deletePost(row)">Delete</button></div></details></div></div>
            </template>
            <template #empty><EmptyState title="No posts found" description="Change the filters or start a new writeup."/></template>
          </DataTable>
        </div>
        <Pagination v-if="meta" class="mt-4" :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :from="meta.from" :to="meta.to" @change="changePage"/>
      </Card>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Layout from '../components/Layout.vue'
import api, { apiErrorMessage } from '../utils/api'
import { useConfirm } from '../ui/useConfirm'
import { useToast } from '../ui/useToast'
import Badge from '../ui/Badge.vue'
import Card from '../ui/Card.vue'
import DataTable from '../ui/DataTable.vue'
import EmptyState from '../ui/EmptyState.vue'
import ErrorBanner from '../ui/ErrorBanner.vue'
import Icon from '../ui/Icon.vue'
import PageHeader from '../ui/PageHeader.vue'
import Pagination from '../ui/Pagination.vue'
import SearchInput from '../ui/SearchInput.vue'

interface PostRow { id:number; title:string; slug:string; excerpt:string; status:string; views_count:number; created_at:string; published_at?:string|null; category?:{name:string}|null }
const route=useRoute();const router=useRouter();const askConfirm=useConfirm();const toast=useToast()
const posts=ref<PostRow[]>([]);const search=ref('');const filter=ref(String(route.query.status||''));const loading=ref(true);const error=ref('');const page=ref(1);const meta=ref<{current_page:number;last_page:number;total:number;from:number;to:number}|null>(null)
const columns=[{key:'title',label:'Post'},{key:'category',label:'Category'},{key:'status',label:'Status'},{key:'views_count',label:'Views'},{key:'published_at',label:'Date'},{key:'actions',label:'Actions'}]
let timer:ReturnType<typeof setTimeout>|undefined
watch(search,()=>{clearTimeout(timer);timer=setTimeout(()=>{page.value=1;void fetchPosts()},300)})
onMounted(()=>{void fetchPosts()})
async function fetchPosts(){loading.value=true;error.value='';try{const {data}=await api.get('/admin/posts',{params:{search:search.value||undefined,status:filter.value||undefined,page:page.value,per_page:15}});posts.value=data.data;meta.value={current_page:data.current_page,last_page:data.last_page,total:data.total,from:data.from||0,to:data.to||0}}catch(e){error.value=apiErrorMessage(e,'Posts could not be loaded.')}finally{loading.value=false}}
async function changePage(next:number){page.value=next;await router.replace({query:{...route.query,page:String(next)}});void fetchPosts()}
async function changeStatus(post:PostRow,status:'draft'|'published'|'archived'){try{await api.patch(`/admin/posts/${post.slug}/status`,{status});toast(`Post moved to ${status}.`);await fetchPosts()}catch(error){toast(apiErrorMessage(error,'Post status could not be changed.'),'error')}}
async function duplicatePost(post:PostRow){try{const {data:original}=await api.get(`/admin/posts/${post.slug}`);const suffix=Math.random().toString(36).slice(2,6);const body={category_id:original.category_id,title:`${original.title} (copy)`,slug:`${original.slug}-copy-${suffix}`,excerpt:original.excerpt,content:original.content,author:original.author,read_time:original.read_time,status:'draft',published_at:null,meta_data:original.meta_data||{},tags:(original.tags||[]).map((tag:{id:number})=>tag.id)};await api.post('/admin/posts',body);toast('Draft copy created.');page.value=1;await fetchPosts()}catch(error){toast(apiErrorMessage(error,'The post could not be duplicated.'),'error')}}
function statusVariant(value:string){return value==='published'?'success':value==='draft'?'warning':value==='archived'?'neutral':'info'}
function formatDate(value?:string|null){return value?new Date(value).toLocaleDateString(undefined,{month:'short',day:'numeric',year:'numeric'}):'—'}
async function deletePost(post:PostRow){if(!await askConfirm(`Delete “${post.title}”? This cannot be undone.`, 'Delete post?'))return;try{await api.delete(`/admin/posts/${post.slug}`);toast('Post deleted.');if(posts.value.length===1&&page.value>1)page.value--;await fetchPosts()}catch(e){toast(apiErrorMessage(e,'Post could not be deleted.'),'error')}}
</script>
