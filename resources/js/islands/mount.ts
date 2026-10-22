import { createApp, type Component } from 'vue'

export function mountIsland(element: HTMLElement, component: Component, props: Record<string, unknown>) {
  createApp(component, props).mount(element)
}
