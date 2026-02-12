<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200">
      <div class="flex flex-col h-full">
        <div class="p-6 border-b border-gray-200">
          <h1 class="text-xl font-bold text-gray-900">Admin Panel</h1>
        </div>

        <nav class="flex-1 p-4 space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.href"
            class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors"
            :class="
              isActive(item.href)
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-700 hover:bg-gray-50'
            "
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" />
            {{ item.name }}
          </router-link>
        </nav>
      </div>
    </div>

    <!-- Main content -->
    <div class="pl-64">
      <header class="bg-white border-b border-gray-200">
        <div class="px-8 py-4">
          <h2 class="text-2xl font-bold text-gray-900">{{ pageTitle }}</h2>
        </div>
      </header>

      <main class="p-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";

interface NavigationItem {
  name: string;
  href: string;
  icon: any;
}

const props = defineProps<{
  pageTitle: string;
}>();

const route = useRoute();

const navigation: NavigationItem[] = [
  { name: "Dashboard", href: "/admin/dashboard", icon: "DashboardIcon" },
  { name: "Posts", href: "/admin/posts", icon: "DocumentIcon" },
  { name: "Projects", href: "/admin/projects", icon: "FolderIcon" },
  { name: "Categories", href: "/admin/categories", icon: "TagIcon" },
  { name: "Tags", href: "/admin/tags", icon: "HashtagIcon" },
  { name: "Contacts", href: "/admin/contacts", icon: "MailIcon" },
  { name: "CVs", href: "/admin/cv", icon: "DocumentTextIcon" },
];

const isActive = (href: string) => {
  if (href === "/admin") {
    return route.path === href;
  }
  return route.path.startsWith(href);
};

// Simple icon components (you can replace with heroicons)
const DashboardIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>',
};
const DocumentIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
};
const FolderIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>',
};
const TagIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>',
};
const HashtagIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>',
};
const MailIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
};
const DocumentTextIcon = {
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
};
</script>
