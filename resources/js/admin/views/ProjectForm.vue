<template>
  <Layout :page-title="isEditing ? 'Edit Project' : 'Create Project'">
    <div class="max-w-4xl">
      <form @submit.prevent="saveProject" class="space-y-6">
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
                class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Slug
              </label>
              <input
                v-model="form.slug"
                type="text"
                class="w-full px-3 py-2 border text-gray-900 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="auto-generated if empty"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Description <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.description"
                required
                rows="3"
                class="w-full px-3 text-gray-900 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Short description"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Full Description
              </label>
              <textarea
                v-model="form.full_description"
                rows="6"
                class="w-full px-3 py-2 border text-gray-900 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Detailed project description"
              />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Links</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Project Link <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.project_link"
                type="url"
                required
                class="w-full px-3 text-gray-900 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="https://example.com"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                GitHub Link
              </label>
              <input
                v-model="form.github_link"
                type="url"
                class="w-full px-3 py-2 text-gray-900 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="https://github.com/username/repo"
              />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Technologies</h3>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Technologies <span class="text-red-500">*</span>
            </label>
            <div class="flex items-center space-x-2 mb-2">
              <input
                v-model="newTech"
                @keypress.enter.prevent="addTechnology"
                type="text"
                class="flex-1 text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g., Vue.js"
              />
              <button
                type="button"
                @click="addTechnology"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
              >
                Add
              </button>
            </div>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(tech, index) in form.technologies"
                :key="index"
                class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full"
              >
                {{ tech }}
                <button
                  type="button"
                  @click="removeTechnology(index)"
                  class="ml-2 text-blue-600 hover:text-blue-800"
                >
                  ×
                </button>
              </span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Status & Settings
          </h3>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Status <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="in-progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Completed Date
              </label>
              <input
                v-model="form.completed_at"
                type="date"
                class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Display Order
              </label>
              <input
                v-model.number="form.order"
                type="number"
                min="0"
                class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div class="flex items-center">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  v-model="form.is_featured"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700"
                  >Featured Project</span
                >
              </label>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Images</h3>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Thumbnail
              </label>
              <input
                @change="handleFileChange($event, 'thumbnail')"
                type="file"
                accept="image/*"
                class="w-full px-3 text-gray-900 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500">Max 5MB</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Main Image
              </label>
              <input
                @change="handleFileChange($event, 'main_image')"
                type="file"
                accept="image/*"
                class="w-full text-gray-900 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-gray-500">Max 5MB</p>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <router-link
            to="/admin/projects"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="saving"
            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? "Saving..." : "Save Project" }}
          </button>
        </div>
      </form>
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import Layout from "../components/Layout.vue";
import api from "../utils/api";
import type { Project } from "../types";

const route = useRoute();
const router = useRouter();
const saving = ref(false);
const isEditing = ref(false);
const newTech = ref("");
//@ts-ignore
const form = ref<Project>({
  title: "",
  slug: "",
  description: "",
  full_description: "",
  project_link: "",
  github_link: "",
  technologies: [],
  status: "in-progress",
  completed_at: "",
  order: 0,
  is_featured: false,
  tags: [],
});

onMounted(async () => {
  const projectId = route.params.id;
  if (projectId) {
    isEditing.value = true;
    const { data } = await api.get(`/admin/projects/${projectId}`);
    form.value = data;
  }
});

const addTechnology = () => {
  if (newTech.value.trim()) {
    form.value.technologies.push(newTech.value.trim());
    newTech.value = "";
  }
};

const removeTechnology = (index: number) => {
  form.value.technologies.splice(index, 1);
};

const handleFileChange = (event: Event, field: "thumbnail" | "main_image") => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.value[field] = target.files[0];
  }
};

const saveProject = async () => {
  saving.value = true;
  try {
    const formData = new FormData();

    Object.entries(form.value).forEach(([key, value]) => {
      if (value !== null && value !== undefined) {
        if (key === "technologies" || key === "tags") {
          formData.append(key, JSON.stringify(value));
        } else if (value instanceof File) {
          formData.append(key, value);
        } else {
          formData.append(key, value.toString());
        }
      }
    });

    if (isEditing.value) {
      await api.post(`/admin/projects/${route.params.id}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    } else {
      await api.post("/admin/projects", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    }

    router.push("/admin/projects");
  } catch (error) {
    alert(error.response?.data?.message || "An error occurred");
  } finally {
    saving.value = false;
  }
};
</script>
