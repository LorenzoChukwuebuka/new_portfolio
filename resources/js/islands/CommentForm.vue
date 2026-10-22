<template>
  <form class="comment-form" novalidate @submit.prevent="submit">
    <section v-if="sent" class="form-message form-message--success" role="status" aria-live="polite">
      <strong>Thank you.</strong> Your comment has been received and is waiting for approval.
    </section>
    <template v-else>
      <div class="comment-form__row">
        <label class="field">
          <span>Name</span>
          <input :id="`${formId}-name`" v-model="form.author_name" name="author_name" required maxlength="120" autocomplete="name" :aria-invalid="Boolean(errors.author_name)" :aria-describedby="errors.author_name ? `${formId}-name-error` : undefined">
          <span v-if="errors.author_name" :id="`${formId}-name-error`" class="field-error">{{ errors.author_name }}</span>
        </label>
        <label class="field">
          <span>Email <span class="field-hint">(not shown publicly)</span></span>
          <input :id="`${formId}-email`" v-model="form.author_email" name="author_email" type="email" required maxlength="255" autocomplete="email" :aria-invalid="Boolean(errors.author_email)" :aria-describedby="errors.author_email ? `${formId}-email-error` : undefined">
          <span v-if="errors.author_email" :id="`${formId}-email-error`" class="field-error">{{ errors.author_email }}</span>
        </label>
      </div>
      <label class="field">
        <span>{{ parentId ? 'Your reply' : 'Comment' }}</span>
        <textarea :id="`${formId}-body`" v-model="form.body" name="body" rows="4" minlength="2" maxlength="2000" required :aria-invalid="Boolean(errors.body)" :aria-describedby="errors.body ? `${formId}-body-error` : undefined" placeholder="Add something thoughtful to the conversation."></textarea>
        <span v-if="errors.body" :id="`${formId}-body-error`" class="field-error">{{ errors.body }}</span>
      </label>
      <div class="comment-form__honeypot" aria-hidden="true">
        <label :for="`${formId}-website`">Leave this field empty</label>
        <input :id="`${formId}-website`" v-model="form.website" name="website" tabindex="-1" autocomplete="off">
      </div>
      <p v-if="failure" class="form-message form-message--error" role="alert" aria-live="assertive">{{ failure }}</p>
      <button class="btn btn-primary" type="submit" :disabled="sending">
        <span v-if="sending" class="spinner" aria-hidden="true"></span>
        {{ sending ? 'Sending…' : parentId ? 'Send reply' : 'Post comment' }}
      </button>
    </template>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

const props = defineProps<{ postSlug: string; parentId?: number | null }>()
const formId = `comment-${Math.random().toString(36).slice(2, 9)}`
const form = reactive({ author_name: '', author_email: '', body: '', website: '', parent_id: props.parentId ?? null as number | null })
const errors = ref<Record<string, string>>({})
const sending = ref(false)
const sent = ref(false)
const failure = ref('')

async function submit() {
  errors.value = {}
  failure.value = ''
  sending.value = true
  try {
    const response = await fetch(`/api/posts/${encodeURIComponent(props.postSlug)}/comments`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
    const data = await response.json().catch(() => ({}))
    if (response.status === 422) {
      errors.value = Object.fromEntries(Object.entries(data.errors || {}).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : String(messages)]))
      failure.value = 'Please correct the highlighted fields.'
      return
    }
    if (!response.ok) throw new Error(data.message || 'The comment could not be sent.')
    sent.value = true
  } catch {
    failure.value = 'The comment could not be sent just now. Please try again.'
  } finally {
    sending.value = false
  }
}
</script>
