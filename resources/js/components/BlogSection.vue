<template>
  <section id="blog" class="py-32 relative bg-zinc-950/50">
    <div
      class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-zinc-700/50 to-transparent"
    />

    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <!-- Section Header -->
      <div class="flex items-end justify-between mb-16">
        <div class="fade-in">
          <p
            class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-3"
          >
            Insights
          </p>
          <h2
            class="font-['Playfair_Display',serif] text-4xl sm:text-5xl font-black text-zinc-100 leading-tight"
          >
            Latest Writing
          </h2>
        </div>
        <a
          href="/blog"
          class="hidden md:inline-flex items-center gap-2 text-sm font-mono text-zinc-500 hover:text-amber-400 transition-colors group"
        >
          More posts
          <svg
            class="w-4 h-4 group-hover:translate-x-1 transition-transform"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </a>
      </div>

      <!-- Loading skeleton -->
      <div
        v-if="loading"
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <div
          v-for="n in 3"
          :key="n"
          class="bg-zinc-900 border border-zinc-800 overflow-hidden animate-pulse"
        >
          <div class="w-full h-44 bg-zinc-800" />
          <div class="p-6 space-y-3">
            <div class="h-4 bg-zinc-800 rounded w-1/3" />
            <div class="h-6 bg-zinc-800 rounded w-3/4" />
            <div class="h-4 bg-zinc-800 rounded w-full" />
          </div>
        </div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center py-16">
        <p class="font-mono text-sm text-zinc-500">Failed to load posts.</p>
      </div>

      <!-- Blog Grid - show 3 latest -->
      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <BlogCard v-for="post in latestPosts" :key="post.id" :post="post" />
      </div>

      <!-- "More Posts" CTA -->
      <div
        class="mt-14 flex flex-col sm:flex-row items-center justify-between gap-6 pt-10 border-t border-zinc-800 fade-in"
      >
        <p class="text-zinc-500 text-sm">
          {{ totalPosts }} articles published — covering architecture,
          performance, and everything in between.
        </p>
        <a
          href="/blog"
          class="inline-flex items-center gap-2 px-7 py-3.5 border border-zinc-700 text-zinc-300 text-sm font-semibold hover:border-amber-400 hover:text-amber-400 transition-all duration-300 hover:-translate-y-0.5 whitespace-nowrap"
        >
          Browse All Posts
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import BlogCard from "./BlogCard.vue";
import type { BlogPost } from "../types";
import api from "../admin/utils/api";
import { normalizeBlogPost } from "../utils/helper";

const posts = ref<BlogPost[]>([]);
const loading = ref(true);
const error = ref(false);

const latestPosts = computed(() => posts.value.slice(0, 3));
const totalPosts = computed(() => posts.value.length);

onMounted(async () => {
  try {
    const { data } = await api.get("/posts");
    posts.value = data.data.map(normalizeBlogPost);
  } catch (e) {
    console.log(e)
    error.value = true;
  } finally {
    loading.value = false;
  }
});
</script>
