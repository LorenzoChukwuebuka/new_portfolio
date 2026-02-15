<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600"
  >
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">
          Enzobyte Admin
        </h1>
        <p class="text-sm text-gray-500 mt-2">
          Sign in to manage your portfolio
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-6">
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Email
          </label>
          <input
            v-model="loginForm.email"
            type="email"
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            placeholder="Enter your email"
          />
          <p v-if="errors.email" class="text-red-500 text-sm mt-1">
            {{ errors.email }}
          </p>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Password
          </label>
          <input
            v-model="loginForm.password"
            type="password"
            class="w-full rounded-lg border border-gray-300 px-4 text-gray-900 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
            placeholder="Enter your password"
          />
          <p v-if="errors.password" class="text-red-500 text-sm mt-1">
            {{ errors.password }}
          </p>
        </div>

        <!-- Button -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 rounded-lg transition disabled:opacity-50"
        >
          <span v-if="!loading">Sign In</span>
          <span v-else>Signing in...</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";
import api from "../utils/api";

const router = useRouter();
const loading = ref(false);

const loginForm = reactive({
  email: "",
  password: "",
});

const errors = reactive({
  email: "",
  password: "",
});

const validate = () => {
  errors.email = "";
  errors.password = "";

  if (!loginForm.email) {
    errors.email = "Please enter email";
  } else if (!/^\S+@\S+\.\S+$/.test(loginForm.email)) {
    errors.email = "Please enter valid email";
  }

  if (!loginForm.password) {
    errors.password = "Please enter password";
  } else if (loginForm.password.length < 6) {
    errors.password = "Password must be at least 6 characters";
  }

  return !errors.email && !errors.password;
};

const handleLogin = async () => {
  if (!validate()) return;

  try {
    loading.value = true;
    await api.login(loginForm.email, loginForm.password);
    router.push("/admin/dashboard");
  } catch (error) {
    alert((error as any).response?.data?.message || "Login failed");
  } finally {
    loading.value = false;
  }
};
</script>
