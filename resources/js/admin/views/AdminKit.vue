<template>
  <Layout page-title="UI kit">
    <div class="space-y-6">
      <PageHeader title="Admin UI kit" description="Local component preview for the shared administration system." />
      <div class="grid gap-4 lg:grid-cols-2">
        <Card class="space-y-4 p-5">
          <h2 class="font-semibold text-adm-ink">Actions and statuses</h2>
          <div class="flex flex-wrap gap-2"><Button>Primary</Button><Button variant="secondary">Secondary</Button><Button variant="ghost">Quiet</Button><Button variant="danger">Danger</Button><Button size="sm">Compact</Button></div>
          <div class="flex flex-wrap gap-2"><Badge>Draft</Badge><Badge variant="success">Published</Badge><Badge variant="warning">Pending</Badge><Badge variant="danger">Failed</Badge><Badge variant="info">Info</Badge></div>
        </Card>
        <Card class="space-y-4 p-5">
          <h2 class="font-semibold text-adm-ink">Form controls</h2>
          <Field id="kit-name" label="Name" hint="Labels stay connected to inputs."><Input id="kit-name" model-value="Lorenzo" /></Field>
          <Field id="kit-notes" label="Notes" error="Example validation message."><Textarea id="kit-notes" model-value="A short note" /></Field>
          <Field id="kit-type" label="Type"><Select id="kit-type"><option>Portfolio</option><option>Article</option></Select></Field>
        </Card>
      </div>
      <Card class="space-y-3 p-5"><h2 class="font-semibold text-adm-ink">Surface and text</h2><p class="text-adm-ink-muted">Muted copy retains readable contrast in both themes.</p><p class="font-mono text-xs text-adm-ink-muted">adm-surface · adm-line · adm-focus</p></Card>
    </div>
  </Layout>
</template>
<script setup lang="ts">
import Layout from '../components/Layout.vue'
import PageHeader from '../ui/PageHeader.vue'
import Card from '../ui/Card.vue'
import Button from '../ui/Button.vue'
import Badge from '../ui/Badge.vue'
import Field from '../ui/Field.vue'
import Input from '../ui/Input.vue'
import Textarea from '../ui/Textarea.vue'
import Select from '../ui/Select.vue'
</script>
