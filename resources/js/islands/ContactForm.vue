<template>
  <div class="contact-form">
    <section v-if="sent" class="form-success" aria-live="polite" aria-atomic="true">
      <p class="eyebrow">Message received</p>
      <h3>Thanks for reaching out.</h3>
      <p>I’ll follow up as soon as I can. A copy of your message has been saved securely.</p>
      <button class="btn btn-secondary" type="button" @click="startAgain">Send another message</button>
    </section>

    <form v-else class="contact-form__fields" novalidate @submit.prevent="submit">
      <div class="contact-form__row">
        <label class="field">
          <span>Name</span>
          <input id="contact-name" v-model="form.name" name="name" autocomplete="name" required maxlength="255" :aria-invalid="Boolean(errors.name)" :aria-describedby="errors.name ? 'contact-name-error' : undefined" placeholder="Your name">
          <span v-if="errors.name" id="contact-name-error" class="field-error">{{ errors.name }}</span>
        </label>
        <label class="field">
          <span>Email</span>
          <input id="contact-email" v-model="form.email" name="email" type="email" autocomplete="email" required maxlength="255" :aria-invalid="Boolean(errors.email)" :aria-describedby="errors.email ? 'contact-email-error' : undefined" placeholder="you@company.com">
          <span v-if="errors.email" id="contact-email-error" class="field-error">{{ errors.email }}</span>
        </label>
      </div>

      <label class="field">
        <span>Subject <span class="field-hint">(optional)</span></span>
        <input id="contact-subject" v-model="form.subject" name="subject" maxlength="255" :aria-invalid="Boolean(errors.subject)" :aria-describedby="errors.subject ? 'contact-subject-error' : undefined" placeholder="What would you like to discuss?">
        <span v-if="errors.subject" id="contact-subject-error" class="field-error">{{ errors.subject }}</span>
      </label>

      <label class="field">
        <span>Message</span>
        <textarea id="contact-message" v-model="form.message" name="message" rows="6" minlength="10" required :aria-invalid="Boolean(errors.message)" :aria-describedby="errors.message ? 'contact-message-error' : 'contact-message-hint'" placeholder="Tell me a little about the work you have in mind."></textarea>
        <span v-if="errors.message" id="contact-message-error" class="field-error">{{ errors.message }}</span>
        <span v-else id="contact-message-hint" class="field-hint">At least 10 characters.</span>
      </label>

      <div class="contact-form__honeypot" aria-hidden="true">
        <label for="contact-website">Leave this field empty</label>
        <input id="contact-website" v-model="form.website" name="website" tabindex="-1" autocomplete="off">
      </div>

      <p v-if="failure" class="form-message form-message--error" role="alert" aria-live="assertive">{{ failure }} <a :href="`mailto:${email}`">Email me instead</a>.</p>
      <div class="contact-form__actions">
        <button class="btn btn-primary btn-lg" type="submit" :disabled="sending">
          <span v-if="sending" class="spinner" aria-hidden="true"></span>
          {{ sending ? 'Sending…' : 'Send message' }}
        </button>
        <span class="field-hint" aria-live="polite">{{ sending ? 'Sending your message securely.' : 'I usually reply within one business day.' }}</span>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'

const props = defineProps<{ email: string }>()

type ContactFormFields = { name: string; email: string; subject: string; message: string; website: string }
const emptyForm = (): ContactFormFields => ({ name: '', email: '', subject: '', message: '', website: '' })
const form = reactive<ContactFormFields>(emptyForm())
const errors = ref<Record<string, string>>({})
const sending = ref(false)
const sent = ref(false)
const failure = ref('')

function resetErrors() {
  errors.value = {}
  failure.value = ''
}

async function submit() {
  resetErrors()
  sending.value = true
  try {
    const response = await fetch('/api/contact', {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    })
    const data = await response.json().catch(() => ({}))
    if (response.status === 422) {
      errors.value = Object.fromEntries(Object.entries(data.errors || {}).map(([key, messages]) => [key, Array.isArray(messages) ? messages[0] : String(messages)]))
      failure.value = 'Please check the highlighted fields and try again.'
      return
    }
    if (!response.ok) throw new Error(data.message || 'The message could not be sent.')
    sent.value = true
  } catch {
    failure.value = `I couldn’t send this message right now. Please try again or contact me at ${props.email}.`
  } finally {
    sending.value = false
  }
}

function startAgain() {
  Object.assign(form, emptyForm())
  resetErrors()
  sent.value = false
}
</script>
