<template>
  <div
    class="min-h-screen bg-slate-950 text-white"
  >
    <div class="mx-auto grid min-h-screen w-full max-w-6xl grid-cols-1 lg:grid-cols-[1fr_440px]">
      <section class="hidden border-r border-white/10 px-10 py-12 lg:flex lg:flex-col lg:justify-between">
        <a href="/" class="text-sm font-semibold text-amber-300">
          Lorenzo.Chukwuebuka
        </a>
        <div class="max-w-xl">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-amber-300">
            Portfolio CMS
          </p>
          <h1 class="mt-5 text-5xl font-semibold leading-tight tracking-tight">
            Keep the portfolio sharp without wrestling the dashboard.
          </h1>
          <p class="mt-5 text-base leading-7 text-slate-400">
            Manage writing, projects, contacts, and CV assets from one calm workspace.
          </p>
        </div>
        <p class="text-xs text-slate-500">Production admin access</p>
      </section>

      <main class="flex items-center justify-center px-5 py-10">
        <div class="w-full max-w-md rounded-lg border border-white/10 bg-white p-8 text-slate-950 shadow-2xl shadow-black/30">
          <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-600">
              Admin Sign In
            </p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight">
              Welcome back
            </h2>
            <p class="mt-2 text-sm text-slate-500">
              Sign in to manage the portfolio content.
            </p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">
                Email
              </label>
              <input
                v-model="loginForm.email"
                type="email"
                autocomplete="email"
                class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-slate-950 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                placeholder="you@example.com"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-rose-600">
                {{ errors.email }}
              </p>
            </div>

            <div>
              <label class="mb-1.5 block text-sm font-medium text-slate-700">
                Password
              </label>
              <input
                v-model="loginForm.password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-slate-950 outline-none transition focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                placeholder="Enter your password"
              />
              <p v-if="errors.password" class="mt-1 text-sm text-rose-600">
                {{ errors.password }}
              </p>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full rounded-md bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            >
              <span v-if="!loading">Sign In</span>
              <span v-else>Signing in...</span>
            </button>
          </form>
        </div>
      </main>
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
