<template>
  <Layout page-title="Dashboard">
    <div class="space-y-6">
      <AdminLoader
        v-if="loading"
        title="Loading dashboard"
        message="Fetching portfolio stats, recent activity, and analytics."
        :rows="5"
      />

      <template v-else>
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="grid grid-cols-1 lg:grid-cols-[1.35fr_0.65fr]">
            <div class="bg-slate-950 p-6 text-white sm:p-8">
              <div class="flex flex-col justify-between gap-8 md:flex-row md:items-start">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-300">
                    Live Portfolio Pulse
                  </p>
                  <h3 class="mt-4 max-w-2xl text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                    {{ stats?.visits.today || 0 }} visits today,
                    {{ totalContentViews }} content views tracked.
                  </h3>
                  <p class="mt-4 max-w-xl text-sm leading-6 text-slate-400">
                    A quick read on what people are opening, reading, and sending back to you.
                  </p>
                </div>
                <a
                  href="/"
                  class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-200"
                >
                  View public site
                </a>
              </div>

              <div class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div
                  v-for="item in heroStats"
                  :key="item.label"
                  class="rounded-md border border-white/10 bg-white/5 p-4"
                >
                  <p class="text-xs font-medium text-slate-400">{{ item.label }}</p>
                  <p class="mt-2 text-2xl font-semibold">{{ item.value }}</p>
                </div>
              </div>
            </div>

            <div class="bg-amber-50 p-6 sm:p-8">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">
                Top performer
              </p>
              <h4 class="mt-4 text-2xl font-semibold tracking-tight text-slate-950">
                {{ topContent?.title || "No views yet" }}
              </h4>
              <p class="mt-2 text-sm text-slate-600">
                {{ topContent ? `${topContent.views} views across tracked content` : "Visits will appear here as people read posts and open projects." }}
              </p>
              <div class="mt-8 rounded-md bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-medium text-slate-700">Unread messages</span>
                  <span class="font-semibold text-rose-700">{{ stats?.contacts.unread || 0 }}</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full bg-rose-500"
                    :style="{ width: `${barWidth(stats?.contacts.unread || 0, Math.max(stats?.contacts.total || 0, 1))}%` }"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div
            v-for="card in metricCards"
            :key="card.label"
            class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                  {{ card.label }}
                </p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                  {{ card.value }}
                </p>
              </div>
              <span class="rounded-md px-2.5 py-1 text-xs font-semibold" :class="card.badgeClass">
                {{ card.badge }}
              </span>
            </div>
            <p class="mt-3 text-sm text-slate-500">{{ card.note }}</p>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full"
                :class="card.barClass"
                :style="{ width: `${card.progress}%` }"
              />
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.25fr_0.75fr]">
          <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h3 class="text-base font-semibold text-slate-950">
                  Page Visits
                </h3>
                <p class="mt-1 text-sm text-slate-500">Last 14 days</p>
              </div>
              <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                Peak {{ maxPageVisits }}
              </span>
            </div>

            <div class="mt-6 flex h-56 items-end gap-2">
              <div
                v-for="item in pageVisitsAnalytics"
                :key="item.date"
                class="flex min-w-0 flex-1 flex-col items-center gap-2"
              >
                <div class="flex h-44 w-full items-end rounded-md bg-slate-50 px-1.5 pb-1.5">
                  <div
                    class="w-full rounded bg-slate-950 transition-all"
                    :style="{ height: `${barWidth(item.visits || 0, maxPageVisits)}%` }"
                    :title="`${item.date}: ${item.visits || 0} visits`"
                  />
                </div>
                <span class="hidden text-[11px] font-medium text-slate-500 sm:block">
                  {{ shortDate(item.date) }}
                </span>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-slate-950">
              Publishing Mix
            </h3>
            <p class="mt-1 text-sm text-slate-500">Content and contact health</p>

            <div class="mt-6 space-y-4">
              <div
                v-for="item in publishingMix"
                :key="item.label"
                class="space-y-2"
              >
                <div class="flex items-center justify-between text-sm">
                  <span class="font-medium text-slate-700">{{ item.label }}</span>
                  <span class="font-semibold text-slate-950">{{ item.value }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                  <div
                    class="h-full rounded-full"
                    :class="item.class"
                    :style="{ width: `${item.progress}%` }"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
          <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
              <h3 class="text-base font-semibold text-slate-950">Top Content</h3>
            </div>
            <div class="grid grid-cols-1 divide-y divide-slate-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
              <div class="p-5">
                <p class="mb-4 text-sm font-semibold text-slate-700">Popular Posts</p>
                <div class="space-y-4">
                  <div
                    v-for="post in popularPosts.slice(0, 5)"
                    :key="post.id"
                    class="grid grid-cols-[1fr_auto] items-center gap-4"
                  >
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-slate-950">{{ post.title }}</p>
                      <p class="text-xs text-slate-500">{{ post.category?.name || "Uncategorized" }}</p>
                    </div>
                    <span class="text-sm font-semibold text-sky-700">{{ post.views_count || 0 }}</span>
                  </div>
                </div>
              </div>

              <div class="p-5">
                <p class="mb-4 text-sm font-semibold text-slate-700">Popular Projects</p>
                <div class="space-y-4">
                  <div
                    v-for="project in popularProjects.slice(0, 5)"
                    :key="project.id"
                    class="grid grid-cols-[1fr_auto] items-center gap-4"
                  >
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-slate-950">{{ project.title }}</p>
                      <p class="text-xs capitalize text-slate-500">{{ project.status }}</p>
                    </div>
                    <span class="text-sm font-semibold text-emerald-700">{{ project.views_count || 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
              <h3 class="text-base font-semibold text-slate-950">Recent Activity</h3>
            </div>
            <div class="divide-y divide-slate-100">
              <div
                v-for="activity in recentActivities.slice(0, 7)"
                :key="`${activity.type}-${activity.title}-${activity.date}`"
                class="px-5 py-4"
              >
                <div class="flex gap-3">
                  <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="getActivityDot(activity.type)" />
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-3">
                      <p class="truncate text-sm font-medium text-slate-950">{{ activity.title }}</p>
                      <span class="rounded px-2 py-1 text-xs font-medium capitalize" :class="getStatusColor(activity.status)">
                        {{ activity.status }}
                      </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ activity.type }} - {{ formatDate(activity.date) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-slate-950">
              Posts Analytics
            </h3>
            <div class="space-y-3">
              <div
                v-for="item in postsAnalytics"
                :key="item.month"
                class="flex items-center justify-between gap-4 rounded-md bg-slate-50 px-3 py-2"
              >
                <span class="text-sm font-medium text-slate-700">{{ item.month }}</span>
                <div class="flex items-center gap-4">
                  <span class="text-sm font-semibold text-sky-700"
                    >{{ item.published }} published</span
                  >
                  <span class="text-sm text-slate-500"
                    >{{ item.total }} total</span
                  >
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-base font-semibold text-slate-950">
              Contact Messages
            </h3>
            <div class="space-y-3">
            <div
              v-for="item in contactsAnalytics"
              :key="item.date"
              class="grid grid-cols-[72px_1fr_auto] items-center gap-3"
            >
              <span class="text-sm font-medium text-slate-600">{{ item.date }}</span>
              <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                  class="h-full rounded-full bg-amber-500"
                  :style="{ width: `${barWidth(item.count || 0, maxContacts)}%` }"
                />
              </div>
              <span class="text-sm font-semibold text-slate-950">
                {{ item.count || 0 }}
              </span>
            </div>
          </div>
          </div>
        </section>
      </template>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import Layout from "../components/Layout.vue";
import AdminLoader from "../components/AdminLoader.vue";
import api from "../utils/api";
import type { DashboardStats, Activity, Post, AnalyticsData } from "../types";

const stats = ref<DashboardStats | null>(null);
const recentActivities = ref<Activity[]>([]);
const popularPosts = ref<Post[]>([]);
const popularProjects = ref<any[]>([]);
const postsAnalytics = ref<AnalyticsData[]>([]);
const pageVisitsAnalytics = ref<AnalyticsData[]>([]);
const contactsAnalytics = ref<AnalyticsData[]>([]);
const loading = ref(true);

const maxPageVisits = computed(() =>
  Math.max(...pageVisitsAnalytics.value.map((item) => item.visits || 0), 1)
);

const maxContacts = computed(() =>
  Math.max(...contactsAnalytics.value.map((item) => item.count || 0), 1)
);

const totalContentViews = computed(
  () => (stats.value?.posts.total_views || 0) + (stats.value?.projects.total_views || 0)
);

const heroStats = computed(() => [
  { label: "All visits", value: stats.value?.visits.total || 0 },
  { label: "Unique today", value: stats.value?.visits.unique_today || 0 },
  { label: "Post views", value: stats.value?.posts.total_views || 0 },
  { label: "Project views", value: stats.value?.projects.total_views || 0 },
]);

const topContent = computed(() => {
  const posts = popularPosts.value.map((post) => ({
    title: post.title,
    views: post.views_count || 0,
  }));
  const projects = popularProjects.value.map((project) => ({
    title: project.title,
    views: project.views_count || 0,
  }));

  return [...posts, ...projects].sort((a, b) => b.views - a.views)[0] || null;
});

const metricCards = computed(() => [
  {
    label: "Site visits",
    value: stats.value?.visits.total || 0,
    badge: `${stats.value?.visits.today || 0} today`,
    note: "Every public page load tracked internally.",
    progress: barWidth(stats.value?.visits.today || 0, Math.max(stats.value?.visits.total || 0, 1)),
    badgeClass: "bg-violet-50 text-violet-700",
    barClass: "bg-violet-500",
  },
  {
    label: "Writing views",
    value: stats.value?.posts.total_views || 0,
    badge: `${stats.value?.posts.published || 0} live`,
    note: `${stats.value?.posts.draft || 0} drafts waiting in the wings.`,
    progress: barWidth(stats.value?.posts.published || 0, Math.max(stats.value?.posts.total || 0, 1)),
    badgeClass: "bg-sky-50 text-sky-700",
    barClass: "bg-sky-500",
  },
  {
    label: "Project views",
    value: stats.value?.projects.total_views || 0,
    badge: `${stats.value?.projects.featured || 0} featured`,
    note: `${stats.value?.projects.completed || 0} completed projects in the library.`,
    progress: barWidth(stats.value?.projects.featured || 0, Math.max(stats.value?.projects.total || 0, 1)),
    badgeClass: "bg-emerald-50 text-emerald-700",
    barClass: "bg-emerald-500",
  },
  {
    label: "Inbox",
    value: stats.value?.contacts.total || 0,
    badge: `${stats.value?.contacts.unread || 0} unread`,
    note: `${stats.value?.contacts.today || 0} messages arrived today.`,
    progress: barWidth(stats.value?.contacts.unread || 0, Math.max(stats.value?.contacts.total || 0, 1)),
    badgeClass: "bg-amber-50 text-amber-700",
    barClass: "bg-amber-500",
  },
]);

const publishingMix = computed(() => [
  {
    label: "Published posts",
    value: stats.value?.posts.published || 0,
    progress: barWidth(stats.value?.posts.published || 0, Math.max(stats.value?.posts.total || 0, 1)),
    class: "bg-sky-500",
  },
  {
    label: "Completed projects",
    value: stats.value?.projects.completed || 0,
    progress: barWidth(stats.value?.projects.completed || 0, Math.max(stats.value?.projects.total || 0, 1)),
    class: "bg-emerald-500",
  },
  {
    label: "Replied messages",
    value: stats.value?.contacts.replied || 0,
    progress: barWidth(stats.value?.contacts.replied || 0, Math.max(stats.value?.contacts.total || 0, 1)),
    class: "bg-violet-500",
  },
]);

onMounted(async () => {
  try {
    await Promise.all([
      fetchStats(),
      fetchRecentActivities(),
      fetchPopularPosts(),
      fetchPopularProjects(),
      fetchPostsAnalytics(),
      fetchPageVisitsAnalytics(),
      fetchContactsAnalytics(),
    ]);
  } finally {
    loading.value = false;
  }
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

const fetchPopularProjects = async () => {
  const { data } = await api.get("/admin/dashboard/popular-projects");
  popularProjects.value = data;
};

const fetchPostsAnalytics = async () => {
  const { data } = await api.get("/admin/dashboard/posts-analytics");
  postsAnalytics.value = data;
};

const fetchPageVisitsAnalytics = async () => {
  const { data } = await api.get("/admin/dashboard/page-visits-analytics");
  pageVisitsAnalytics.value = data;
};

const fetchContactsAnalytics = async () => {
  const { data } = await api.get("/admin/dashboard/contacts-analytics");
  contactsAnalytics.value = data;
};

const barWidth = (value: number, max: number) => {
  return max === 0 ? 0 : Math.max(4, Math.round((value / max) * 100));
};

const shortDate = (date: string | undefined) => {
  return date ? date.split(" ")[1] || date : "";
};

const getActivityDot = (type: string) => {
  const colors = {
    post: "bg-sky-500",
    project: "bg-emerald-500",
    contact: "bg-amber-500",
  };
  return colors[type as keyof typeof colors] || "bg-slate-400";
};

const getTypeColor = (type: string) => {
  const colors = {
    post: "bg-sky-50 text-sky-700",
    project: "bg-emerald-50 text-emerald-700",
    contact: "bg-amber-50 text-amber-700",
  };
  return colors[type as keyof typeof colors] || "bg-slate-100 text-slate-700";
};

const getStatusColor = (status: string) => {
  const colors = {
    published: "bg-emerald-50 text-emerald-700",
    draft: "bg-amber-50 text-amber-700",
    archived: "bg-slate-100 text-slate-700",
    completed: "bg-emerald-50 text-emerald-700",
    "in-progress": "bg-sky-50 text-sky-700",
    unread: "bg-rose-50 text-rose-700",
    read: "bg-sky-50 text-sky-700",
    replied: "bg-emerald-50 text-emerald-700",
  };
  return colors[status as keyof typeof colors] || "bg-slate-100 text-slate-700";
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
