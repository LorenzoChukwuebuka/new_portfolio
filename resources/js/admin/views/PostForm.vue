<template>
  <Layout :page-title="isEditing ? 'Edit Post' : 'Create Post'">
    <div class="max-w-4xl">
      <form @submit.prevent="savePost" class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Basic Information
          </h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Slug
              </label>
              <input
                v-model="form.slug"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="auto-generated if empty"
              />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Category
                </label>
                <select
                  v-model="form.category_id"
                  class="w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option :value="undefined">Uncategorized</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Author
                </label>
                <input
                  v-model="form.author"
                  type="text"
                  class="w-full px-3 py-2 border text-gray-900 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Excerpt <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.excerpt"
                required
                rows="2"
                class="w-full px-3 py-2 text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Brief summary of the post"
              />
            </div>

            <!-- Tags Field -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tags
              </label>
              <select
                v-model="form.tags"
                multiple
                class="w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                style="min-height: 100px;"
              >
                <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                  {{ tag.name }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500">
                Hold Ctrl/Cmd to select multiple tags
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Content</h3>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Content <span class="text-red-500">*</span>
            </label>
            <div
              ref="editorContainer"
              class="bg-white border text-gray-900 border-gray-300 rounded-lg"
            ></div>
          </div>
        </div>

        <!-- Metadata Section -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Metadata</h3>

          <div class="space-y-3">
            <div
              v-for="(value, key, index) in form.meta_data"
              :key="index"
              class="flex gap-2"
            >
              <input
                v-model="metaKeys[index]"
                @blur="updateMetaKey(index, key)"
                type="text"
                placeholder="Key"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <input
                v-model="form.meta_data[key]"
                type="text"
                placeholder="Value"
                class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <button
                @click="removeMetaField(key)"
                type="button"
                class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100"
              >
                Remove
              </button>
            </div>

            <button
              @click="addMetaField"
              type="button"
              class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100"
            >
              + Add Metadata Field
            </button>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Publishing</h3>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Status <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-3 py-2 border text-gray-900 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Published Date
              </label>
              <input
                v-model="form.published_at"
                type="datetime-local"
                class="w-full px-3 py-2 text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Read Time (minutes)
              </label>
              <input
                v-model.number="form.read_time"
                type="number"
                min="1"
                class="w-full px-3 py-2 text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Featured Image
          </h3>

          <div>
            <input
              @change="handleFileChange"
              type="file"
              accept="image/*"
              class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="mt-1 text-xs text-gray-500">Max 5MB</p>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <router-link
            to="/admin/posts"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? "Saving..." : "Save Post" }}
          </button>
        </div>
      </form>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useRoute, useRouter } from "vue-router";
import Quill from "quill";
import "quill/dist/quill.snow.css";
import Layout from "../components/Layout.vue";
import api from "../utils/api";
import type { Post, Category } from "../types";

interface Tag {
  id: number;
  name: string;
}

const route = useRoute();
const router = useRouter();
const saving = ref(false);
const isEditing = ref(false);
const editorContainer = ref<HTMLElement | null>(null);
const categories = ref<Category[]>([]);
const tags = ref<Tag[]>([]);
const metaKeys = ref<string[]>([]);
let quillEditor: Quill | null = null;

const form = ref<Post>({
  category_id: undefined,
  title: "",
  slug: "",
  excerpt: "",
  content: "",
  author: "",
  read_time: undefined,
  status: "draft",
  published_at: "",
  meta_data: {} as any,
  tags: [],
});

onMounted(async () => {
  await fetchCategories();
  await fetchTags();

  // Wait for DOM to be fully updated
  await nextTick();

  // Initialize Quill editor
  if (editorContainer.value) {
    quillEditor = new Quill(editorContainer.value, {
      theme: "snow",
      modules: {
        toolbar: [
          [{ header: [1, 2, 3, 4, 5, 6, false] }],
          ["bold", "italic", "underline", "strike"],
          ["blockquote", "code-block"],
          [{ list: "ordered" }, { list: "bullet" }],
          [{ script: "sub" }, { script: "super" }],
          [{ indent: "-1" }, { indent: "+1" }],
          [{ color: [] }, { background: [] }],
          [{ align: [] }],
          ["link", "image", "video"],
          ["clean"],
        ],
      },
      placeholder: "Write your post content here...",
    });
  }

  const postId = route.params.slug;

  if (postId) {
    isEditing.value = true;
    const { data } = await api.get(`/admin/posts/${postId}`);

    const post = data;

    if (post.meta_data && typeof post.meta_data === "string") {
      post.meta_data = JSON.parse(post.meta_data);
    }
    form.value.meta_data = post.meta_data || {};
    //@ts-ignore
    metaKeys.value = Object.keys(form.value.meta_data);

    form.value = {
      ...form.value,
      ...post,
      tags: post.tags ? post.tags.map((t: any) => t.id) : [],
      meta_data: post.meta_data || {},
    };

    if (quillEditor && data.content) {
      quillEditor.root.innerHTML = data.content;
    }
  }
});

onBeforeUnmount(() => {
  quillEditor = null;
});

const fetchCategories = async () => {
  const { data } = await api.get("/admin/categories");
  categories.value = data.data;
};

const fetchTags = async () => {
  const { data } = await api.get("/admin/tags");
  tags.value = data.data;
};

const addMetaField = () => {
  const newKey = `key_${Date.now()}`;
  //@ts-ignore
  form.value.meta_data[newKey] = "";
  metaKeys.value.push(newKey);
};

const removeMetaField = (key: string) => {
  //@ts-ignore
  delete form.value.meta_data[key];
  const index = metaKeys.value.indexOf(key);
  if (index > -1) {
    metaKeys.value.splice(index, 1);
  }
};

const updateMetaKey = (index: number, oldKey: string) => {
  const newKey = metaKeys.value[index];
  if (newKey !== oldKey && newKey) {
    //@ts-ignore
    const value = form.value.meta_data[oldKey];
    //@ts-ignore
    delete form.value.meta_data[oldKey];
    //@ts-ignore
    form.value.meta_data[newKey] = value;
  }
};

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.value.featured_image = target.files[0];
  }
};

const savePost = async () => {
  if (!quillEditor) return;

  saving.value = true;
  try {
    form.value.content = quillEditor.root.innerHTML;

    const formData = new FormData();

    Object.entries(form.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        if (key === "meta_data") {
          formData.append(key, JSON.stringify(value));
        } else if (key === "tags") {
          // Tags sent as JSON array
          formData.append(key, JSON.stringify(value));
        } else if (value instanceof File) {
          formData.append(key, value);
        } else {
          formData.append(key, value.toString());
        }
      }
    });

    if (isEditing.value) {
      formData.append("_method", "put");
      await api.post(`/admin/posts/${route.params.slug}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    } else {
      await api.post("/admin/posts", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }

    router.push("/admin/posts");
  } catch (error) {
    //@ts-ignore
    alert(error.response?.data?.message || "An error occurred");
  } finally {
    saving.value = false;
  }
};
</script>

<style>
.ql-container {
  min-height: 300px;
  font-size: 16px;
}

.ql-editor {
  min-height: 300px;
}
</style>
