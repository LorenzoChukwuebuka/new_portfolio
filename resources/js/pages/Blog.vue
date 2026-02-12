<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <AppNavbar />

    <main class="max-w-7xl mx-auto px-6 lg:px-8 pt-32 pb-24">
      <!-- Breadcrumb -->
      <nav
        class="flex items-center gap-2 text-xs font-mono text-zinc-600 mb-12"
      >
        <a href="/" class="hover:text-zinc-400 transition-colors">home</a>
        <span>/</span>
        <span class="text-amber-400">blog</span>
      </nav>

      <!-- Page Header -->
      <div class="mb-14">
        <p
          class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-3"
        >
          Insights & Writeups
        </p>
        <h1
          class="font-['Playfair_Display',serif] text-5xl sm:text-6xl font-black text-zinc-100 leading-tight mb-5"
        >
          The Blog
        </h1>
        <p class="text-zinc-400 text-lg max-w-2xl leading-relaxed">
          Notes on software architecture, performance engineering, developer
          tooling, and the occasional rant about things that shouldn't be hard.
        </p>
      </div>

      <!-- Loading skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="n in 6"
          :key="n"
          class="bg-zinc-900 border border-zinc-800 overflow-hidden animate-pulse"
        >
          <div class="w-full h-44 bg-zinc-800" />
          <div class="p-6 space-y-3">
            <div class="h-4 bg-zinc-800 rounded w-1/3" />
            <div class="h-6 bg-zinc-800 rounded w-3/4" />
            <div class="h-4 bg-zinc-800 rounded w-full" />
            <div class="h-4 bg-zinc-800 rounded w-4/5" />
          </div>
        </div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-24">
        <p class="font-mono text-sm text-zinc-500">Failed to load posts.</p>
      </div>

      <template v-else>
        <!-- Category Filter -->
        <div class="flex flex-wrap gap-2 mb-12">
          <button
            v-for="cat in allCategories"
            :key="cat"
            @click="activeCategory = cat"
            :class="[
              'px-3 py-1.5 text-xs font-mono transition-all duration-200',
              activeCategory === cat
                ? 'bg-amber-400 text-zinc-950 font-semibold'
                : 'bg-zinc-800 text-zinc-500 border border-zinc-700 hover:border-zinc-500 hover:text-zinc-300',
            ]"
          >
            {{ cat }}
            <span v-if="cat !== 'All'" class="ml-1 text-zinc-600">
              ({{ postsByCategory(cat).length }})
            </span>
          </button>
        </div>

        <!-- Stats bar -->
        <div
          class="flex items-center gap-4 mb-10 text-xs font-mono text-zinc-600 border-b border-zinc-800 pb-6"
        >
          <span>{{ filteredPosts.length }} articles</span>
          <span class="text-zinc-800">•</span>
          <span>{{ totalReadTime }} min total read time</span>
        </div>

        <!-- Blog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <TransitionGroup name="list">
            <BlogCard
              v-for="post in filteredPosts"
              :key="post.id"
              :post="post"
            />
          </TransitionGroup>
        </div>

        <!-- Empty state -->
        <div v-if="filteredPosts.length === 0" class="text-center py-24">
          <p class="text-zinc-600 font-mono text-sm">
            No posts in that category yet.
          </p>
          <button
            @click="activeCategory = 'All'"
            class="mt-4 text-amber-400 text-sm font-mono hover:text-amber-300 transition-colors"
          >
            Show all posts →
          </button>
        </div>
      </template>
    </main>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import AppNavbar from "../components/AppNavbar.vue";
import AppFooter from "../components/AppFooter.vue";
import BlogCard from "../components/BlogCard.vue";
import type { BlogPost } from "../types";
import api from "../admin/utils/api";
import { normalizeBlogPost } from "../utils/helper";

const posts = ref<BlogPost[]>([]);
const loading = ref(true);
const error = ref(false);
const activeCategory = ref("All");

onMounted(async () => {
  try {
    const { data } = await api.get("/posts");
    posts.value = data.data.map(normalizeBlogPost);
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

const allCategories = computed(() => {
  const cats = new Set<string>(["All"]);
  posts.value.forEach((p) => cats.add(p.category));
  return Array.from(cats);
});

const filteredPosts = computed(() =>
  activeCategory.value === "All"
    ? posts.value
    : posts.value.filter((p) => p.category === activeCategory.value)
);

function postsByCategory(cat: string) {
  return posts.value.filter((p) => p.category === cat);
}

const totalReadTime = computed(() =>
  filteredPosts.value.reduce((sum, post) => {
    const mins = parseInt(post.readTime);
    return sum + (isNaN(mins) ? 0 : mins);
  }, 0)
);
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
