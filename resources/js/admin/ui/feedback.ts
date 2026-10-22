import type { ToastKind } from './useToast'

export function showToast(message: string, kind: ToastKind = 'error') {
  window.dispatchEvent(new CustomEvent('admin:toast', { detail: { message, kind } }))
}

export function confirmAction(message: string, title = 'Please confirm'): Promise<boolean> {
  return new Promise((resolve) => window.dispatchEvent(new CustomEvent('admin:confirm', { detail: { message, title, resolve } })))
}
