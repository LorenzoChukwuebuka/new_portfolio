export type ToastKind = 'success' | 'error' | 'info'
export interface ToastMessage { id: number; message: string; kind: ToastKind }

export function useToast() {
  return (message: string, kind: ToastKind = 'success') => {
    window.dispatchEvent(new CustomEvent('admin:toast', { detail: { message, kind } }))
  }
}
