<template>
  <article
    class="group bg-zinc-900 border border-zinc-800 overflow-hidden hover:border-amber-400/60 transition-all duration-400 hover:-translate-y-1 flex flex-col"
  >
    <!-- Image -->
    <div
      class="relative w-full h-44 bg-gradient-to-br from-zinc-800/80 to-zinc-900 flex items-center justify-center overflow-hidden"
    >
      <img
        v-if="post.featuredUrl"
        :src="post.featuredUrl"
        :alt="post.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
      />
      <span
        v-else
        class="text-5xl select-none group-hover:scale-110 transition-transform duration-500"
      >
        📝
      </span>
      <!-- Category badge -->
      <span
        class="absolute top-4 left-4 font-mono text-xs text-amber-400/70 bg-zinc-950/60 border border-amber-400/20 px-2 py-1 rounded"
      >
        {{ post.category }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-1">
      <div class="flex items-center gap-3 text-xs text-zinc-500 font-mono mb-3">
        <span>{{ post.readTime }}</span>
        <span class="text-zinc-700">•</span>
        <span>{{ post.date }}</span>
      </div>

      <h3
        class="font-['Playfair_Display',serif] text-xl font-bold text-zinc-100 leading-snug mb-3 group-hover:text-amber-400 transition-colors duration-300"
      >
        {{ post.title }}
      </h3>

      <p class="text-zinc-400 text-sm leading-relaxed mb-5 flex-1">
        {{ post.excerpt }}
      </p>

      <!-- Tags -->
      <div class="flex flex-wrap gap-1.5 mb-5">
        <span
          v-for="tag in post.tags"
          :key="tag"
          class="px-2 py-0.5 text-xs font-mono text-zinc-500 bg-zinc-800 rounded-sm"
        >
          #{{ tag.toLowerCase().replace(' ', '-') }}
        </span>
      </div>

      <a
        :href="`/blog/${post.slug}`"
        class="inline-flex items-center gap-2 text-sm font-semibold text-amber-400 hover:text-amber-300 transition-colors group/link mt-auto"
      >
        Read Article
        <svg
          class="w-4 h-4 group-hover/link:translate-x-1 transition-transform"
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
  </article>
</template>

<script setup lang="ts">
import type { BlogPost } from '../types'

defineProps<{
  post: BlogPost
}>()
</script>