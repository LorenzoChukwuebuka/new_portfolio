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

        <!-- Comments -->
        <section class="mt-16 border-t border-zinc-800 pt-10">
          <div class="mb-8 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
              <p class="font-mono text-amber-400 text-xs tracking-widest uppercase mb-2">
                Discussion
              </p>
              <h2 class="font-['Playfair_Display',serif] text-3xl font-black text-zinc-100">
                Comments
              </h2>
            </div>
            <p class="text-sm font-mono text-zinc-600">
              {{ totalComments }} {{ totalComments === 1 ? "comment" : "comments" }}
            </p>
          </div>

          <form
            @submit.prevent="submitComment()"
            class="mb-10 border border-zinc-800 bg-zinc-900/60 p-5"
          >
            <input
              v-model="commentForm.website"
              type="text"
              tabindex="-1"
              autocomplete="off"
              class="hidden"
              aria-hidden="true"
            />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <input
                v-model="commentForm.author_name"
                type="text"
                required
                placeholder="Name"
                class="w-full border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
              />
              <input
                v-model="commentForm.author_email"
                type="email"
                required
                placeholder="Email"
                class="w-full border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
              />
            </div>
            <textarea
              v-model="commentForm.body"
              required
              rows="4"
              placeholder="Join the conversation..."
              class="mt-4 w-full resize-y border border-zinc-800 bg-zinc-950 px-4 py-3 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
            />
            <div class="mt-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
              <p class="text-xs text-zinc-600">
                Your email is used only for comment identity and is not shown publicly.
              </p>
              <button
                type="submit"
                :disabled="commentSubmitting"
                class="inline-flex items-center justify-center bg-amber-400 px-5 py-2.5 text-sm font-semibold text-zinc-950 transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {{ commentSubmitting ? "Posting..." : "Post Comment" }}
              </button>
            </div>
            <p v-if="commentNotice" class="mt-3 text-sm text-emerald-400">
              {{ commentNotice }}
            </p>
            <p v-if="commentError" class="mt-3 text-sm text-rose-400">
              {{ commentError }}
            </p>
          </form>

          <div v-if="commentsLoading" class="space-y-4">
            <div v-for="n in 3" :key="n" class="animate-pulse border border-zinc-800 bg-zinc-900/50 p-5">
              <div class="h-4 w-1/3 rounded bg-zinc-800" />
              <div class="mt-4 h-4 rounded bg-zinc-800" />
              <div class="mt-2 h-4 w-4/5 rounded bg-zinc-800" />
            </div>
          </div>

          <div v-else-if="comments.length === 0" class="border border-dashed border-zinc-800 p-8 text-center">
            <p class="text-sm text-zinc-500">
              No comments yet. Be the first to add a thought.
            </p>
          </div>

          <div v-else class="space-y-5">
            <article
              v-for="comment in comments"
              :key="comment.id"
              class="border border-zinc-800 bg-zinc-900/40 p-5"
            >
              <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-400/15 text-sm font-bold text-amber-400">
                  {{ initials(comment.author_name) }}
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="font-semibold text-zinc-200">{{ comment.author_name }}</p>
                    <span class="text-xs font-mono text-zinc-600">{{ formatCommentDate(comment.created_at) }}</span>
                  </div>
                  <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-zinc-400">{{ comment.body }}</p>
                  <button
                    type="button"
                    class="mt-3 text-xs font-mono text-zinc-600 transition hover:text-amber-400"
                    @click="startReply(comment)"
                  >
                    Reply
                  </button>

                  <form
                    v-if="replyingTo === comment.id"
                    @submit.prevent="submitComment(comment.id)"
                    class="mt-4 border-l-2 border-amber-400/40 pl-4"
                  >
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <input
                        v-model="replyForm.author_name"
                        type="text"
                        required
                        placeholder="Name"
                        class="w-full border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
                      />
                      <input
                        v-model="replyForm.author_email"
                        type="email"
                        required
                        placeholder="Email"
                        class="w-full border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
                      />
                    </div>
                    <textarea
                      v-model="replyForm.body"
                      required
                      rows="3"
                      placeholder="Write a reply..."
                      class="mt-3 w-full resize-y border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-amber-400/70"
                    />
                    <div class="mt-3 flex justify-end gap-2">
                      <button
                        type="button"
                        class="px-3 py-2 text-xs font-semibold text-zinc-500 transition hover:text-zinc-300"
                        @click="cancelReply"
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        :disabled="commentSubmitting"
                        class="bg-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-950 transition hover:bg-amber-300 disabled:opacity-60"
                      >
                        Reply
                      </button>
                    </div>
                  </form>

                  <div v-if="comment.replies?.length" class="mt-5 space-y-4 border-l border-zinc-800 pl-4">
                    <article
                      v-for="reply in comment.replies"
                      :key="reply.id"
                      class="bg-zinc-950/50 p-4"
                    >
                      <div class="flex items-start gap-3">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-xs font-bold text-zinc-300">
                          {{ initials(reply.author_name) }}
                        </div>
                        <div>
                          <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-zinc-200">{{ reply.author_name }}</p>
                            <span class="text-xs font-mono text-zinc-600">{{ formatCommentDate(reply.created_at) }}</span>
                          </div>
                          <p class="mt-2 whitespace-pre-wrap text-sm leading-6 text-zinc-400">{{ reply.body }}</p>
                        </div>
                      </div>
                    </article>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </section>

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
import { computed, reactive, ref, onMounted } from "vue";
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
const comments = ref<BlogComment[]>([]);
const commentsLoading = ref(false);
const commentSubmitting = ref(false);
const commentNotice = ref("");
const commentError = ref("");
const replyingTo = ref<number | null>(null);
const currentSlug = ref("");

interface BlogComment {
  id: number;
  parent_id: number | null;
  author_name: string;
  body: string;
  created_at: string;
  replies: BlogComment[];
}

const commentForm = reactive({
  author_name: "",
  author_email: "",
  body: "",
  website: "",
});

const replyForm = reactive({
  author_name: "",
  author_email: "",
  body: "",
});

const totalComments = computed(() =>
  comments.value.reduce((total, comment) => total + 1 + (comment.replies?.length || 0), 0)
);

onMounted(async () => {
  const slug = props.slug ?? window.location.pathname.split("/").pop();
  currentSlug.value = slug || "";

  try {
    const { data } = await api.get(`/posts/${slug}`);
    post.value = normalizeBlogPost(data);
    prevPost.value = data.prev ?? null;
    nextPost.value = data.next ?? null;
    await fetchComments();
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
});

async function fetchComments() {
  if (!currentSlug.value) return;

  commentsLoading.value = true;
  try {
    const { data } = await api.get(`/posts/${currentSlug.value}/comments`);
    comments.value = data;
  } finally {
    commentsLoading.value = false;
  }
}

async function submitComment(parentId?: number) {
  if (!currentSlug.value) return;

  commentSubmitting.value = true;
  commentNotice.value = "";
  commentError.value = "";

  const source = parentId ? replyForm : commentForm;

  try {
    const { data } = await api.post(`/posts/${currentSlug.value}/comments`, {
      author_name: source.author_name,
      author_email: source.author_email,
      body: source.body,
      parent_id: parentId,
      website: parentId ? "" : commentForm.website,
    });

    source.author_name = "";
    source.author_email = "";
    source.body = "";
    if (!parentId) commentForm.website = "";
    replyingTo.value = null;
    commentNotice.value = data.message || "Thanks. Your comment is waiting for approval.";
    await fetchComments();
  } catch (error: any) {
    commentError.value = error.response?.data?.message || "Could not post comment.";
  } finally {
    commentSubmitting.value = false;
  }
}

function startReply(comment: BlogComment) {
  replyingTo.value = comment.id;
  commentNotice.value = "";
  commentError.value = "";
}

function cancelReply() {
  replyingTo.value = null;
  replyForm.author_name = "";
  replyForm.author_email = "";
  replyForm.body = "";
}

function initials(name: string) {
  return name
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}

function formatCommentDate(date: string) {
  return new Date(date).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}
</script>
