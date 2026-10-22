<template>
  <main class="grid min-h-svh grid-cols-[minmax(0,1fr)] bg-adm-bg text-adm-ink lg:grid-cols-[minmax(0,1fr)_minmax(22rem,34rem)]">
    <section class="relative hidden overflow-hidden bg-adm-sidebar-bg px-10 py-12 text-adm-sidebar-ink lg:flex lg:flex-col lg:justify-between xl:px-16">
      <a href="/" class="relative z-10 text-sm font-semibold">{{ shortName }}<span class="text-adm-accent">.</span></a>
      <div class="relative z-10 max-w-2xl"><p class="eyebrow text-adm-accent">Portfolio CMS</p><h1 class="mt-5 font-display text-5xl font-semibold leading-tight tracking-tight">Keep the portfolio sharp without wrestling the dashboard.</h1><p class="mt-5 max-w-xl text-base leading-7 text-adm-sidebar-ink/75">Manage writing, projects, contacts, and CV assets from one calm workspace.</p></div>
      <div class="absolute inset-0 opacity-30" aria-hidden="true" style="background-image:radial-gradient(ellipse at 70% 30%,var(--adm-accent),transparent 42%)"/><p class="relative z-10 text-xs text-adm-sidebar-ink/65">Private editorial workspace</p>
    </section>
    <section class="flex min-h-svh min-w-0 items-center justify-center px-5 py-10 sm:px-8">
      <div class="w-full min-w-0 max-w-md">
        <div class="mb-6 flex justify-end"><ThemeToggle/></div>
        <div class="rounded-lg border border-adm-line bg-adm-surface p-6 shadow-adm-card sm:p-9">
          <p class="eyebrow text-adm-accent">Admin sign in</p><h2 class="mt-3 text-2xl font-semibold tracking-tight">Welcome back</h2><p class="mt-2 text-sm text-adm-ink-muted">Sign in to manage the portfolio content.</p>
          <form class="mt-7 grid gap-5" novalidate @submit.prevent="handleLogin">
            <div><label for="login-email" class="mb-1.5 block text-sm font-semibold">Email</label><input id="login-email" ref="emailInput" v-model.trim="loginForm.email" type="email" autocomplete="username" inputmode="email" required :aria-invalid="Boolean(errors.email)" :aria-describedby="errors.email ? 'login-email-error' : undefined" class="min-h-11 w-full rounded-md border border-adm-line bg-adm-surface-2 px-3 py-2.5 text-adm-ink placeholder:text-adm-ink-muted focus:border-adm-focus focus:outline-none focus:ring-2 focus:ring-adm-focus/30" placeholder="you@example.com"><p v-if="errors.email" id="login-email-error" class="mt-1 text-sm text-adm-danger-ink">{{ errors.email }}</p></div>
            <div><label for="login-password" class="mb-1.5 block text-sm font-semibold">Password</label><div class="flex min-h-11 overflow-hidden rounded-md border border-adm-line bg-adm-surface-2 focus-within:border-adm-focus focus-within:ring-2 focus-within:ring-adm-focus/30"><input id="login-password" v-model="loginForm.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required :aria-invalid="Boolean(errors.password)" :aria-describedby="errors.password ? 'login-password-error' : undefined" class="min-w-0 flex-1 border-0 bg-transparent px-3 text-adm-ink focus:outline-none" placeholder="Enter your password"><button class="min-w-16 px-3 text-xs font-semibold text-adm-ink-muted hover:text-adm-ink" type="button" :aria-pressed="showPassword" @click="showPassword = !showPassword">{{ showPassword ? 'Hide' : 'Show' }}</button></div><p v-if="errors.password" id="login-password-error" class="mt-1 text-sm text-adm-danger-ink">{{ errors.password }}</p></div>
            <p v-if="failure" class="rounded-md border border-adm-danger-ink/25 bg-adm-danger-bg p-3 text-sm text-adm-danger-ink" role="alert">{{ failure }}</p>
            <button type="submit" :disabled="loading" class="mt-1 inline-flex min-h-12 items-center justify-center gap-2 rounded-md bg-adm-accent-bg px-4 py-3 text-sm font-semibold text-adm-accent-ink hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60"><span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"/>{{ loading ? 'Signing in…' : 'Sign in' }}</button>
          </form>
        </div>
        <p class="mt-4 text-center text-xs text-adm-ink-muted"><a href="/" class="hover:text-adm-ink">Return to the public site</a></p>
      </div>
    </section>
  </main>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api, { apiErrorMessage, fieldErrors } from '../utils/api'
import ThemeToggle from '../ui/ThemeToggle.vue'
const router = useRouter()
const loading = ref(false)
const showPassword = ref(false)
const emailInput = ref<HTMLInputElement>()
const failure = ref('')
const errors = reactive({ email: '', password: '' })
const loginForm = reactive({ email: '', password: '' })
const shortName = 'Lorenzo.Chukwuebuka'
function validate() {
  errors.email = loginForm.email && /^\S+@\S+\.\S+$/.test(loginForm.email) ? '' : 'Enter a valid email address.'
  errors.password = loginForm.password ? '' : 'Enter your password.'
  if (errors.email) emailInput.value?.focus()
  else if (errors.password) document.getElementById('login-password')?.focus()
  return !errors.email && !errors.password
}
async function handleLogin() {
  failure.value = ''
  if (!validate()) return
  loading.value = true
  try { await api.login(loginForm.email, loginForm.password); await router.push('/admin/dashboard') }
  catch (error) {
    const mapped = fieldErrors(error)
    errors.email = mapped.email || ''
    errors.password = mapped.password || ''
    const status = (error as { response?: { status?: number } })?.response?.status
    failure.value = status === 429 ? 'Too many attempts. Wait a moment before trying again.' : apiErrorMessage(error, 'Sign in failed. Check your details and try again.')
    if (errors.email) emailInput.value?.focus()
  } finally { loading.value = false }
}
</script>
