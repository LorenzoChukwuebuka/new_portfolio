export function useConfirm() {
  return (message: string, title = 'Please confirm'): Promise<boolean> => new Promise((resolve) => {
    window.dispatchEvent(new CustomEvent('admin:confirm', { detail: { message, title, resolve } }))
  })
}
