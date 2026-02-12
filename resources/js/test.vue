<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
</script>

<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton class="bg-blue-500 text-white px-4 py-2 rounded">
      Options
    </MenuButton>

    <MenuItems class="absolute mt-2 w-56 bg-white shadow-lg rounded">
      <MenuItem v-slot="{ active }">
        <a
          href="#"
          :class="[
            active ? 'bg-gray-100' : '',
            'block px-4 py-2 text-sm text-gray-700'
          ]"
        >
          Edit
        </a>
      </MenuItem>
    </MenuItems>
  </Menu>
</template>
