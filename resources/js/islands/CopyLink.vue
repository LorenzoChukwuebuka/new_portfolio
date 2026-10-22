<template>
  <div class="copy-link">
    <button class="btn btn-secondary" type="button" @click="copy">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 15 15 9M8 7H6a3 3 0 0 0 0 6h2m8-2h2a3 3 0 0 0 0-6h-2M8 17h8a3 3 0 0 0 0-6h-1" /></svg>
      {{ copied ? 'Link copied' : 'Copy link' }}
    </button>
    <span class="field-hint" aria-live="polite">{{ message }}</span>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const copied = ref(false)
const message = ref('')

async function copy() {
  try {
    await navigator.clipboard.writeText(window.location.href)
    copied.value = true
    message.value = 'Article link copied to clipboard.'
  } catch {
    const input = document.createElement('textarea')
    input.value = window.location.href
    input.setAttribute('readonly', '')
    input.style.position = 'fixed'
    input.style.opacity = '0'
    document.body.append(input)
    input.select()
    const success = document.execCommand('copy')
    input.remove()
    copied.value = success
    message.value = success ? 'Article link copied to clipboard.' : 'Select and copy the page address from your browser.'
  }
  window.setTimeout(() => { copied.value = false }, 2500)
}
</script>
