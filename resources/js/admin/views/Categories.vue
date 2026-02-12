<template>
  <Layout page-title="Categories">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Manage your blog categories</p>
        </div>
        <button
          @click="openModal()"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Add Category
        </button>
      </div>

      <!-- Categories List -->
      <div class="bg-white rounded-lg border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Name
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Slug
                </th>
                <th
                  class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Description
                </th>
                <th
                  class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="category in categories"
                :key="category.id"
                class="hover:bg-gray-50"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ category.name }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">{{ category.slug }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-500 max-w-md truncate">
                    {{ category.description || "-" }}
                  </div>
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                >
                  <button
                    @click="openModal(category)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteCategory(category)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <TransitionRoot :show="isOpen" as="template">
      <Dialog @close="closeModal" class="relative z-50">
        <TransitionChild
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-25" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4">
            <TransitionChild
              enter="ease-out duration-300"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel
                class="w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 shadow-xl transition-all"
              >
                <DialogTitle class="text-lg font-semibold text-gray-900 mb-4">
                  {{ editingCategory ? "Edit Category" : "Add Category" }}
                </DialogTitle>

                <form @submit.prevent="saveCategory" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Name <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      required
                      class="w-full px-3 bg-white py-2 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Technology"
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
                      placeholder="technology (auto-generated if empty)"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Description
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="A brief description of this category"
                    />
                  </div>

                  <div class="flex justify-end space-x-3 pt-4">
                    <button
                      type="button"
                      @click="closeModal"
                      class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      :disabled="saving"
                      class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                      {{ saving ? "Saving..." : "Save" }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </Layout>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionRoot,
  TransitionChild,
} from "@headlessui/vue";
import Layout from "../components/Layout.vue";
import api from "../utils/api";
import type { Category } from "../types";

const categories = ref<Category[]>([]);
const isOpen = ref(false);
const editingCategory = ref<Category | null>(null);
const saving = ref(false);

const form = ref<Category>({
  name: "",
  slug: "",
  description: "",
});

onMounted(async () => {
  await fetchCategories();
});

const fetchCategories = async () => {
  const { data } = await api.get("/admin/categories");
 // console.log(data, "categories");
  categories.value = data.data;
};

const openModal = (category?: Category) => {
  if (category) {
    editingCategory.value = category;
    form.value = { ...category };
  } else {
    editingCategory.value = null;
    form.value = { name: "", slug: "", description: "" };
  }
  isOpen.value = true;
};

const closeModal = () => {
  isOpen.value = false;
  editingCategory.value = null;
  form.value = { name: "", slug: "", description: "" };
};

const saveCategory = async () => {
  saving.value = true;
  try {
    if (editingCategory.value) {
      await api.put(
        `/admin/categories/${editingCategory.value.id}`,
        form.value
      );
    } else {
      await api.post("/admin/categories", form.value);
    }
    await fetchCategories();
    closeModal();
  } catch (error:any) {
    alert(error.response?.data?.message || "An error occurred");
  } finally {
    saving.value = false;
  }
};

const deleteCategory = async (category: Category) => {
  if (!confirm(`Are you sure you want to delete "${category.name}"?`)) return;

  try {
    await api.delete(`/admin/categories/${category.id}`);
    await fetchCategories();
  } catch (error:any) {
    alert(error.response?.data?.message || "An error occurred");
  }
};
</script>
