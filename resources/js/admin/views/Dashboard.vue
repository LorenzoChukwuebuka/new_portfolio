<template>
  <Layout page-title="Dashboard">
    <div class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Posts"
          :value="stats?.posts.total || 0"
          :subtitle="`${stats?.posts.published || 0} published`"
          color="blue"
        />
        <StatCard
          title="Projects"
          :value="stats?.projects.total || 0"
          :subtitle="`${stats?.projects.completed || 0} completed`"
          color="green"
        />
        <StatCard
          title="Messages"
          :value="stats?.contacts.total || 0"
          :subtitle="`${stats?.contacts.unread || 0} unread`"
          color="yellow"
        />
        <StatCard
          title="Total Views"
          :value="stats?.posts.total_views || 0"
          subtitle="All posts"
          color="purple"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Posts Analytics -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Posts Analytics
          </h3>
          <div class="space-y-3">
            <div
              v-for="item in postsAnalytics"
              :key="item.month"
              class="flex items-center justify-between"
            >
              <span class="text-sm text-gray-600">{{ item.month }}</span>
              <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-blue-600"
                  >{{ item.published }} published</span
                >
                <span class="text-sm text-gray-500"
                  >{{ item.total }} total</span
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Analytics -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Contact Messages (7 days)
          </h3>
          <div class="space-y-3">
            <div
              v-for="item in contactsAnalytics"
              :key="item.date"
              class="flex items-center justify-between"
            >
              <span class="text-sm text-gray-600">{{ item.date }}</span>
              <span class="text-sm font-medium text-gray-900"
                >{{ item.count }} messages</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Recent Activities</h3>
        </div>
        <div class="divide-y divide-gray-200">
          <div
            v-for="activity in recentActivities"
            :key="activity.url"
            class="p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <span
                  class="px-2 py-1 text-xs font-medium rounded capitalize"
                  :class="getTypeColor(activity.type)"
                >
                  {{ activity.type }}
                </span>
                <a
                  :href="activity.url"
                  class="text-sm font-medium text-gray-900 hover:text-blue-600"
                >
                  {{ activity.title }}
                </a>
              </div>
              <div class="flex items-center space-x-4">
                <span
                  class="px-2 py-1 text-xs font-medium rounded capitalize"
                  :class="getStatusColor(activity.status)"
                >
                  {{ activity.status }}
                </span>
                <span class="text-sm text-gray-500">{{
                  formatDate(activity.date)
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Popular Posts -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Popular Posts</h3>
        </div>
        <div class="divide-y divide-gray-200">
          <div
            v-for="post in popularPosts"
            :key="post.id"
            class="p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900">
                  {{ post.title }}
                </h4>
                <p class="text-sm text-gray-500">
                  {{ post.category?.name || "Uncategorized" }}
                </p>
              </div>
              <span class="text-sm font-semibold text-blue-600"
                >{{ post.views_count }} views</span
              >
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import Layout from "../components/Layout.vue";
import StatCard from "../components/StatCard.vue";
import api from "../utils/api";
import type { DashboardStats, Activity, Post, AnalyticsData } from "../types";

const stats = ref<DashboardStats | null>(null);
const recentActivities = ref<Activity[]>([]);
const popularPosts = ref<Post[]>([]);
const postsAnalytics = ref<AnalyticsData[]>([]);
const contactsAnalytics = ref<AnalyticsData[]>([]);

onMounted(async () => {
  await Promise.all([
    fetchStats(),
    fetchRecentActivities(),
    fetchPopularPosts(),
    fetchPostsAnalytics(),
    fetchContactsAnalytics(),
  ]);
});

const fetchStats = async () => {
  const { data } = await api.get("/admin/dashboard");
  stats.value = data;
};

const fetchRecentActivities = async () => {
  const { data } = await api.get("/admin/dashboard/recent-activities");
  recentActivities.value = data;
};

const fetchPopularPosts = async () => {
  const { data } = await api.get("/admin/dashboard/popular-posts");
  popularPosts.value = data;
};

const fetchPostsAnalytics = async () => {
  const { data } = await api.get("/admin/dashboard/posts-analytics");
  postsAnalytics.value = data;
};

const fetchContactsAnalytics = async () => {
  const { data } = await api.get("/admin/dashboard/contacts-analytics");
  contactsAnalytics.value = data;
};

const getTypeColor = (type: string) => {
  const colors = {
    post: "bg-blue-100 text-blue-700",
    project: "bg-green-100 text-green-700",
    contact: "bg-yellow-100 text-yellow-700",
  };
  return colors[type as keyof typeof colors] || "bg-gray-100 text-gray-700";
};

const getStatusColor = (status: string) => {
  const colors = {
    published: "bg-green-100 text-green-700",
    draft: "bg-yellow-100 text-yellow-700",
    archived: "bg-gray-100 text-gray-700",
    completed: "bg-green-100 text-green-700",
    "in-progress": "bg-blue-100 text-blue-700",
    unread: "bg-red-100 text-red-700",
    read: "bg-blue-100 text-blue-700",
    replied: "bg-green-100 text-green-700",
  };
  return colors[status as keyof typeof colors] || "bg-gray-100 text-gray-700";
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>
