import axios, { AxiosError, AxiosHeaders } from 'axios'
import { appLogger } from '../lib/logger'

export const backendUrl = import.meta.env.VITE_BACKEND_URL ?? 'http://localhost:25200'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? `${backendUrl}/api`,
  withCredentials: true,
  withXSRFToken: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

function readCookie(name: string): string | null {
  if (typeof document === 'undefined') {
    return null
  }

  const cookie = document.cookie.split('; ').find((item) => item.startsWith(`${name}=`))

  if (!cookie) {
    return null
  }

  return decodeURIComponent(cookie.slice(name.length + 1))
}

http.interceptors.request.use((config) => {
  const xsrfToken = readCookie('XSRF-TOKEN')

  if (xsrfToken) {
    const headers = AxiosHeaders.from(config.headers)
    headers.set('X-XSRF-TOKEN', xsrfToken)
    config.headers = headers
  }

  appLogger.log('http.request', {
    method: config.method,
    url: config.url,
    params: config.params,
    has_xsrf_token: Boolean(xsrfToken),
  })

  return config
})

http.interceptors.response.use(
  (response) => {
    appLogger.log('http.response', {
      method: response.config.method,
      url: response.config.url,
      status: response.status,
    })

    return response
  },
  (error: AxiosError) => {
    appLogger.log('http.error', {
      method: error.config?.method,
      url: error.config?.url,
      status: error.response?.status,
      message: error.message,
    })

    return Promise.reject(error)
  },
)

export async function csrfCookie() {
  appLogger.log('csrfCookie.start')
  await axios.get(`${backendUrl}/sanctum/csrf-cookie`, {
    headers: {
      Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
  })
  appLogger.log('csrfCookie.done')
}
