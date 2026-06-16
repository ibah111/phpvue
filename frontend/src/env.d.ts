/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL?: string
  readonly VITE_BACKEND_URL?: string
  readonly VITE_DEV_PROXY_TARGET?: string
  readonly VITE_ALLOWED_HOSTS?: string
  readonly VITE_PORT?: string
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'

  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}
