<template>
  <Layout page-title="Comments">
    <div class="space-y-6">
      <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
        >
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
            {{ card.label }}
          </p>
          <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
            {{ card.value }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
          <div>
            <h3 class="text-lg font-semibold text-slate-950">Discussion Queue</h3>
            <p class="mt-1 text-sm text-slate-500">
              Review comments before they appear publicly.
            </p>
          </div>
          <div class="flex flex-col gap-3 sm:flex-row">
            <input
              v-model="search"
              type="search"
              placeholder="Search comments..."
              class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 outline-none focus:border-slate-950"
              @input="scheduleFetch"
            />
            <select
              v-model="status"
              class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 outline-none focus:border-slate-950"
              @change="fetchComments"
            >
              <option value="all">All comments</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="spam">Spam</option>
            </select>
          </div>
        </div>
      </div>

      <AdminLoader
        v-if="loading"
        title="Loading comments"
        message="Fetching the moderation queue."
        :rows="5"
      />

      <div v-else class="space-y-4">
        <article
          v-for="comment in comments"
          :key="comment.id"
          class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
        >
          <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-3">
                <p class="font-semibold text-slate-950">{{ comment.author_name }}</p>
                <span class="text-sm text-slate-500">{{ comment.author_email }}</span>
                <span class="rounded px-2 py-1 text-xs font-medium capitalize" :class="statusClass(comment.status)">
                  {{ comment.status }}
                </span>
                <span v-if="comment.parent_id" class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-600">
                  Reply
                </span>
              </div>
              <p class="mt-2 text-xs text-slate-500">
                On {{ comment.post?.title || "Deleted post" }} - {{ formatDate(comment.created_at) }}
              </p>
              <p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">
                {{ comment.body }}
              </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
              <button
                v-if="comment.status !== 'approved'"
                class="rounded-md bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                @click="changeStatus(comment.id, 'approve')"
              >
                Approve
              </button>
              <button
                v-if="comment.status !== 'pending'"
                class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                @click="changeStatus(comment.id, 'pending')"
              >
                Pending
              </button>
              <button
                v-if="comment.status !== 'spam'"
                class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                @click="changeStatus(comment.id, 'spam')"
              >
                Spam
              </button>
              <button
                class="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                @click="deleteComment(comment)"
              >
                Delete
              </button>
            </div>
          </div>
        </article>

        <div
          v-if="comments.length === 0"
          class="rounded-lg border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500"
        >
          No comments match this filter.
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import Layout from "../components/Layout.vue";
import AdminLoader from "../components/AdminLoader.vue";
import api from "../utils/api";

interface CommentItem {
  id: number;
  parent_id: number | null;
  author_name: string;
  author_email: string;
  body: string;
  status: "pending" | "approved" | "spam";
  created_at: string;
  post?: { title: string; slug: string };
}

const comments = ref<CommentItem[]>([]);
const stats = ref({ total: 0, pending: 0, approved: 0, spam: 0 });
const status = ref("pending");
const search = ref("");
const loading = ref(true);
let timeout: ReturnType<typeof setTimeout>;

const statCards = computed(() => [
  { label: "All", value: stats.value.total },
  { label: "Pending", value: stats.value.pending },
  { label: "Approved", value: stats.value.approved },
  { label: "Spam", value: stats.value.spam },
]);

onMounted(async () => {
  await Promise.all([fetchComments(), fetchStats()]);
});

async function fetchStats() {
  const { data } = await api.get("/admin/comments/stats");
  stats.value = data;
}

async function fetchComments() {
  loading.value = true;
  try {
    const { data } = await api.get("/admin/comments", {
      params: { status: status.value, search: search.value },
    });
    comments.value = data.data;
  } finally {
    loading.value = false;
  }
}

function scheduleFetch() {
  clearTimeout(timeout);
  timeout = setTimeout(fetchComments, 250);
}

async function changeStatus(id: number, action: string) {
  await api.post(`/admin/comments/${id}/${action}`);
  await Promise.all([fetchComments(), fetchStats()]);
}

async function deleteComment(comment: CommentItem) {
  if (!confirm(`Delete comment from "${comment.author_name}"?`)) return;
  await api.delete(`/admin/comments/${comment.id}`);
  await Promise.all([fetchComments(), fetchStats()]);
}

function statusClass(commentStatus: string) {
  const classes = {
    pending: "bg-amber-50 text-amber-700",
    approved: "bg-emerald-50 text-emerald-700",
    spam: "bg-rose-50 text-rose-700",
  };

  return classes[commentStatus as keyof typeof classes] || "bg-slate-100 text-slate-700";
}

function formatDate(date: string) {
  return new Date(date).toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
</script>
