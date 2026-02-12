<template>
  <div class="min-h-screen bg-zinc-950 text-zinc-100">
    <AppNavbar />

    <main class="max-w-3xl mx-auto px-6 lg:px-8 pt-32 pb-24">
      <!-- Breadcrumb -->
      <nav
        class="flex items-center gap-2 text-xs font-mono text-zinc-600 mb-12"
      >
        <a href="/" class="hover:text-zinc-400 transition-colors">home</a>
        <span>/</span>
        <a href="/blog" class="hover:text-zinc-400 transition-colors">blog</a>
        <span>/</span>
        <span class="text-amber-400">{{ post?.slug ?? "..." }}</span>
      </nav>

      <!-- Loading skeleton -->
      <div v-if="loading" class="animate-pulse space-y-6">
        <div class="h-4 bg-zinc-800 rounded w-1/4" />
        <div class="h-12 bg-zinc-800 rounded w-3/4" />
        <div class="h-6 bg-zinc-800 rounded w-full" />
        <div class="h-6 bg-zinc-800 rounded w-4/5" />
        <div class="h-48 bg-zinc-800 rounded w-full mt-6" />
      </div>

      <!-- Error / 404 -->
      <div v-else-if="error || !post" class="text-center py-32">
        <p
          class="font-['Playfair_Display',serif] text-6xl font-black text-zinc-800 mb-4"
        >
          404
        </p>
        <p class="text-zinc-600 font-mono text-sm mb-6">Post not found.</p>
        <a
          href="/blog"
          class="text-amber-400 text-sm font-mono hover:text-amber-300 transition-colors"
        >
          ← Back to blog
        </a>
      </div>

      <template v-else>
        <!-- Category + Meta -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
          <span
            class="px-2.5 py-1 text-xs font-mono text-amber-400 bg-amber-400/10 border border-amber-400/20"
          >
            {{ post.category }}
          </span>
          <span class="text-xs font-mono text-zinc-600">{{
            post.readTime
          }}</span>
          <span class="text-zinc-800">•</span>
          <span class="text-xs font-mono text-zinc-600">{{ post.date }}</span>
        </div>

        <!-- Title -->
        <h1
          class="font-['Playfair_Display',serif] text-4xl sm:text-5xl font-black text-zinc-100 leading-tight mb-6"
        >
          {{ post.title }}
        </h1>

        <!-- Excerpt / Lead -->
        <p
          class="text-zinc-400 text-xl leading-relaxed mb-10 border-l-2 border-amber-400/40 pl-5"
        >
          {{ post.excerpt }}
        </p>

        <!-- Tags -->
        <div class="flex flex-wrap gap-2 mb-10 pb-10 border-b border-zinc-800">
          <span
            v-for="tag in post.tags"
            :key="tag"
            class="px-2.5 py-1 text-xs font-mono text-zinc-500 bg-zinc-800"
          >
            #{{ tag.toLowerCase().replace(" ", "-") }}
          </span>
        </div>

        <!-- Article Body -->
        <div
          class="prose-content space-y-6 text-zinc-400 leading-loose"
          v-html="post.content"
        />

        <!-- Author / Share Block -->
        <div
          class="mt-16 pt-10 border-t border-zinc-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-11 h-11 rounded-full bg-amber-400/20 border border-amber-400/30 flex items-center justify-center font-mono text-amber-400 font-bold text-sm"
            >
              {{ post.author?.charAt(0).toUpperCase() ?? "A" }}
            </div>
            <div>
              <p class="text-sm font-semibold text-zinc-200">
                {{ post.author }}
              </p>
              <p class="text-xs text-zinc-600 font-mono">Software Engineer</p>
            </div>
          </div>
          <div class="flex gap-3">
            <a
              :href="`https://twitter.com/intent/tweet?text=${encodeURIComponent(
                post.title
              )}`"
              target="_blank"
              class="text-xs font-mono text-zinc-600 hover:text-zinc-300 transition-colors"
            >
              Share on Twitter →
            </a>
          </div>
        </div>

        <!-- Next/Prev Nav -->
        <div v-if="prevPost || nextPost" class="grid grid-cols-2 gap-4 mt-12">
          <a
            v-if="prevPost"
            :href="`/blog/${prevPost.slug}`"
            class="group p-5 bg-zinc-900 border border-zinc-800 hover:border-zinc-600 transition-colors"
          >
            <p class="text-xs font-mono text-zinc-600 mb-2">← Previous</p>
            <p
              class="text-sm text-zinc-300 group-hover:text-zinc-100 transition-colors line-clamp-2"
            >
              {{ prevPost.title }}
            </p>
          </a>
          <a
            v-if="nextPost"
            :href="`/blog/${nextPost.slug}`"
            class="group p-5 bg-zinc-900 border border-zinc-800 hover:border-zinc-600 transition-colors text-right col-start-2"
          >
            <p class="text-xs font-mono text-zinc-600 mb-2">Next →</p>
            <p
              class="text-sm text-zinc-300 group-hover:text-zinc-100 transition-colors line-clamp-2"
            >
              {{ nextPost.title }}
            </p>
          </a>
        </div>

        <!-- Back to blog -->
        <div class="mt-10 text-center">
          <a
            href="/blog"
            class="inline-flex items-center gap-2 text-sm font-mono text-zinc-600 hover:text-amber-400 transition-colors"
          >
            ← Back to all posts
          </a>
        </div>
      </template>
    </main>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import AppNavbar from "../components/AppNavbar.vue";
import AppFooter from "../components/AppFooter.vue";
import type { BlogPost } from "../types";
import api from "../admin/utils/api";
import { normalizeBlogPost } from "../utils/helper";

const props = defineProps<{
  slug?: string;
}>();

const post = ref<BlogPost | null>(null);
const prevPost = ref<{ slug: string; title: string } | null>(null);
const nextPost = ref<{ slug: string; title: string } | null>(null);
const loading = ref(true);
const error = ref(false);

onMounted(async () => {
  const slug = props.slug ?? window.location.pathname.split("/").pop();

  try {
    const { data } = await api.get(`/posts/${slug}`);
    post.value = normalizeBlogPost(data);
    prevPost.value = data.prev ?? null;
    nextPost.value = data.next ?? null;
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
});
</script>
