<template>
  <div class="min-h-screen bg-slate-100 text-slate-950">
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <aside
      class="fixed inset-y-0 left-0 z-40 w-72 border-r border-slate-200 bg-slate-950 text-white transition-transform duration-200 lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex h-full flex-col">
        <div class="border-b border-white/10 px-6 py-5">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-300">
            Portfolio CMS
          </p>
          <h1 class="mt-2 text-lg font-semibold tracking-tight">
            Lorenzo Admin
          </h1>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-5">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.href"
            class="group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors"
            :class="
              isActive(item.href)
                ? 'bg-white text-slate-950 shadow-sm'
                : 'text-slate-300 hover:bg-white/10 hover:text-white'
            "
            @click="sidebarOpen = false"
          >
            <component
              :is="item.icon"
              class="h-5 w-5 shrink-0"
              :class="isActive(item.href) ? 'text-amber-500' : 'text-slate-500 group-hover:text-slate-200'"
            />
            {{ item.name }}
          </router-link>
        </nav>

        <div class="border-t border-white/10 p-4">
          <a
            href="/"
            class="flex items-center justify-between rounded-md border border-white/10 px-3 py-2 text-sm text-slate-300 transition-colors hover:border-amber-300/50 hover:text-white"
          >
            <span>View Site</span>
            <span aria-hidden="true">↗</span>
          </a>
        </div>
      </div>
    </aside>

    <div class="lg:pl-72">
      <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
          <div class="flex min-w-0 items-center gap-3">
            <button
              type="button"
              class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-600 lg:hidden"
              @click="sidebarOpen = true"
              aria-label="Open sidebar"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <div class="min-w-0">
              <p class="text-xs font-medium uppercase tracking-[0.18em] text-slate-500">
                Admin Workspace
              </p>
              <h2 class="truncate text-2xl font-semibold tracking-tight text-slate-950">
                {{ pageTitle }}
              </h2>
            </div>
          </div>

          <button
            type="button"
            class="rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:border-slate-300 hover:bg-slate-50"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </header>

      <main class="px-4 py-6 sm:px-6 lg:px-8">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../utils/api";

interface NavigationItem {
  name: string;
  href: string;
  icon: any;
}

defineProps<{
  pageTitle: string;
}>();

const route = useRoute();
const router = useRouter();
const sidebarOpen = ref(false);

const isActive = (href: string) => {
  if (href === "/admin") {
    return route.path === href;
  }
  return route.path.startsWith(href);
};

const logout = async () => {
  try {
    await api.logout();
  } finally {
    router.push("/admin");
  }
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

const navigation: NavigationItem[] = [
  { name: "Dashboard", href: "/admin/dashboard", icon: DashboardIcon },
  { name: "Posts", href: "/admin/posts", icon: DocumentIcon },
  { name: "Projects", href: "/admin/projects", icon: FolderIcon },
  { name: "Categories", href: "/admin/categories", icon: TagIcon },
  { name: "Tags", href: "/admin/tags", icon: HashtagIcon },
  { name: "Contacts", href: "/admin/contacts", icon: MailIcon },
  { name: "CVs", href: "/admin/cv", icon: DocumentTextIcon },
];
</script>
