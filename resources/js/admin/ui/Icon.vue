<template><svg :class="className" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path v-for="(d,index) in paths[name] || paths.grid" :key="index" :d="d"/></svg></template>
<script setup lang="ts">
defineProps<{ name: 'grid' | 'file' | 'folder' | 'tag' | 'hash' | 'mail' | 'chat' | 'download' | 'settings' | 'plus' | 'menu' | 'search' | 'sun' | 'moon'; className?: string }>()
const paths: Record<string, string[]> = {
  grid: ['M3 3h7v7H3z','M14 3h7v7h-7z','M14 14h7v7h-7z','M3 14h7v7H3z'],
  file: ['M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z','M14 2v6h6','M8 13h8','M8 17h8'],
  folder: ['M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
  tag: ['M20.5 13.5 13.5 20.5a2 2 0 0 1-2.8 0l-7.2-7.2V4h9.3l7.7 7.7a1.3 1.3 0 0 1 0 1.8z','M7.5 7.5h.01'],
  hash: ['M4 9h16','M3 15h16','M10 3 8 21','M16 3l-2 18'],
  mail: ['M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z','m22 7-10 6L2 7'],
  chat: ['M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z'],
  download: ['M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4','m7 10 5 5 5-5','M12 15V3'],
  settings: ['M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z','M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8-.1 1.7 1.7 0 0 0-.9 1.5v.2h-3.4v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.1l-.2.1-1.7-2.9.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-.9H5.8v-3.4H6a1.7 1.7 0 0 0 1.5-.9 1.7 1.7 0 0 0-.3-1.9l-.1-.1 1.7-2.9.2.1a1.7 1.7 0 0 0 1.8.1 1.7 1.7 0 0 0 .9-1.5V3h3.4v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.1l.2-.1 1.7 2.9-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5.9h.2v3.4h-.2a1.7 1.7 0 0 0-1.5 1.4z'],
  plus: ['M12 5v14','M5 12h14'],
  menu: ['M4 6h16','M4 12h16','M4 18h16'],
  search: ['m21 21-4.3-4.3','M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16z'],
  sun: ['M12 3v2','M12 19v2','m4.2 4.2 1.4 1.4','m18.4 18.4 1.4 1.4','M3 12h2','M19 12h2','m4.2 19.8 1.4-1.4','m18.4 5.6 1.4-1.4','M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z'],
  moon: ['M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13z'],
}
</script>
