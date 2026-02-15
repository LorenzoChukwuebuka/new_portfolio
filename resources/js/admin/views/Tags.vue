<template>
  <Layout page-title="Tags">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">Manage your blog tags</p>
        </div>
        <button
          @click="openModal()"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Add Tag
        </button>
      </div>

      <!-- Tags List -->
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
              <tr v-for="tag in tags" :key="tag.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ tag.name }}
                  </div>
                </td>

                <td class="px-6 py-4">
                  <div class="text-sm text-gray-500 max-w-md truncate">
                    {{ tag.description || "-" }}
                  </div>
                </td>

                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                >
                  <button
                    @click="openModal(tag)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>

                  <button
                    @click="deleteTag(tag)"
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
                  {{ editingTag ? "Edit Tag" : "Add Tag" }}
                </DialogTitle>

                <form @submit.prevent="saveTag" class="space-y-4">
                  <!-- Name -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Name <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.name"
                      required
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="VueJS"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Description
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Optional description"
                    />
                  </div>

                  <!-- Actions -->
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
//import type { Tag } from "../types";

export interface Tag {
  id?: number;
  name: string;
  description?: string;
}

const tags = ref<Tag[]>([]);
const isOpen = ref(false);
const editingTag = ref<Tag | null>(null);
const saving = ref(false);

const form = ref<Tag>({
  name: "",
  description: "",
});

onMounted(async () => {
  await fetchTags();
});

const fetchTags = async () => {
  const { data } = await api.get("/admin/tags");
  tags.value = data.data;
};

const openModal = (tag?: Tag) => {
  if (tag) {
    editingTag.value = tag;
    form.value = { ...tag };
  } else {
    editingTag.value = null;
    form.value = { name: "", description: "" };
  }
  isOpen.value = true;
};

const closeModal = () => {
  isOpen.value = false;
  editingTag.value = null;
  form.value = { name: "", description: "" };
};

const saveTag = async () => {
  saving.value = true;
  try {
    if (editingTag.value) {
      await api.put(`/admin/tags/${editingTag.value.id}`, form.value);
    } else {
      await api.post("/admin/tags", form.value);
    }
    await fetchTags();
    closeModal();
  } catch (error:any) {
    alert(error.response?.data?.message || "An error occurred");
  } finally {
    saving.value = false;
  }
};

const deleteTag = async (tag: Tag) => {
  if (!confirm(`Are you sure you want to delete "${tag.name}"?`)) return;

  try {
    await api.delete(`/admin/tags/${tag.id}`);
    await fetchTags();
  } catch (error:any) {
    alert(error.response?.data?.message || "An error occurred");
  }
};
</script>
