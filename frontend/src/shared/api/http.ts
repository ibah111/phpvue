import axios, { AxiosError } from 'axios'
import { appLogger } from '../lib/logger'

export const backendUrl = import.meta.env.VITE_BACKEND_URL ?? 'http://localhost:25200'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? `${backendUrl}/api`,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

http.interceptors.request.use((config) => {
  appLogger.log('http.request', {
    method: config.method,
    url: config.url,
    params: config.params,
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
  await axios.get(`${backendUrl}/sanctum/csrf-cookie`, { withCredentials: true })
  appLogger.log('csrfCookie.done')
}
