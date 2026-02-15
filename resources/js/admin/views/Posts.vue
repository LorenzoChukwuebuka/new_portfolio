<template>
  <Layout page-title="Posts">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <select
            v-model="filter"
            @change="fetchPosts"
            class="px-3 py-2 border text-gray-900 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Posts</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <router-link
          to="/admin/posts/create"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Add Post
        </router-link>
      </div>

      <!-- Posts Table -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Title
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Category
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Status
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Views
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Date
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">
                    {{ post.title }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ post.excerpt?.substring(0, 60) }}...
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">
                    {{ post.category?.name || "Uncategorized" }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="px-2 py-1 text-xs font-medium rounded capitalize"
                    :class="getStatusColor(post.status)"
                  >
                    {{ post.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ post.views_count || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(post.created_at) }}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                >
                  <router-link
                    :to="`/admin/posts/${post.slug}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </router-link>
                  <button
                    @click="deletePost(post)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
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
import api from "../utils/api";
import type { Post } from "../types";

const posts = ref<Post[]>([]);
const filter = ref("");

onMounted(async () => {
  await fetchPosts();
});

const fetchPosts = async () => {
  const params = filter.value ? { status: filter.value } : {};
  const { data } = await api.get("/admin/posts", { params });
  posts.value = data.data;
};

const getStatusColor = (status: string) => {
  const colors = {
    published: "bg-green-100 text-green-700",
    draft: "bg-yellow-100 text-yellow-700",
    archived: "bg-gray-100 text-gray-700",
  };
  return colors[status as keyof typeof colors] || "bg-gray-100 text-gray-700";
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
