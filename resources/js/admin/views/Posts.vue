<template>
  <Layout page-title="Posts">
    <div class="space-y-6">
      <!-- Header -->
      <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
          <div>
            <h3 class="text-lg font-semibold text-slate-950">Content Library</h3>
            <p class="mt-1 text-sm text-slate-500">
              Draft, publish, and manage portfolio articles.
            </p>
          </div>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            v-model="search"
            @input="fetchPosts"
            type="search"
            placeholder="Search posts..."
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10 sm:w-64"
          />
          <select
            v-model="filter"
            @change="fetchPosts"
            class="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
          >
            <option value="">All Posts</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
          </select>
          <router-link
            to="/admin/posts/create"
            class="inline-flex items-center justify-center rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
          >
            Add Post
          </router-link>
          </div>
        </div>
      </div>

      <AdminLoader
        v-if="loading"
        title="Loading posts"
        message="Fetching articles from the database."
        :rows="6"
      />

      <!-- Posts Table -->
      <div v-else class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Title
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Category
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Status
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Views
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Date
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="post in posts" :key="post.id" class="hover:bg-slate-50">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-slate-950">
                    {{ post.title }}
                  </div>
                  <div class="mt-1 max-w-xl text-sm text-slate-500">
                    {{ post.excerpt?.substring(0, 60) }}...
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-500">
                    {{ post.category?.name || "Uncategorized" }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="rounded px-2 py-1 text-xs font-medium capitalize"
                    :class="getStatusColor(post.status)"
                  >
                    {{ post.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ post.views_count || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ formatDate(post.created_at) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                >
                  <router-link
                    :to="`/admin/posts/${post.slug}/edit`"
                    class="text-sky-700 hover:text-sky-900"
                  >
                    Edit
                  </router-link>
                  <button
                    @click="deletePost(post)"
                    class="text-rose-600 hover:text-rose-800"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="posts.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                  No posts found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import Layout from "../components/Layout.vue";
import AdminLoader from "../components/AdminLoader.vue";
import api from "../utils/api";
import type { Post } from "../types";

const posts = ref<Post[]>([]);
const filter = ref("");
const search = ref("");
const loading = ref(true);

onMounted(async () => {
  await fetchPosts();
});

const fetchPosts = async () => {
  const params: Record<string, string> = {};
  if (filter.value) params.status = filter.value;
  if (search.value) params.search = search.value;
  loading.value = true;
  try {
    const { data } = await api.get("/admin/posts", { params });
    posts.value = data.data;
  } finally {
    loading.value = false;
  }
};

const getStatusColor = (status: string) => {
  const colors = {
    published: "bg-emerald-50 text-emerald-700",
    draft: "bg-amber-50 text-amber-700",
    archived: "bg-slate-100 text-slate-700",
  };
  return colors[status as keyof typeof colors] || "bg-slate-100 text-slate-700";
};

const formatDate = (date?: string) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const deletePost = async (post: Post) => {
  if (!confirm(`Are you sure you want to delete "${post.title}"?`)) return;

  try {
    await api.delete(`/admin/posts/${post.slug}`);
    await fetchPosts();
  } catch (error:any) {
    alert(error.response?.data?.message || "An error occurred");
  }
};
</script>
