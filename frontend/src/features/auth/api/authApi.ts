import { csrfCookie, http } from '../../../shared/api/http'
import type { ResourceResponse, User } from '../../../shared/api/types'
import { appLogger } from '../../../shared/lib/logger'

export interface LoginPayload {
  email: string
  password: string
}

export async function login(payload: LoginPayload): Promise<User> {
  appLogger.log('authApi.login.start', { email: payload.email })
  await csrfCookie()
  const response = await http.post<ResourceResponse<User>>('/auth/login', payload)
  appLogger.log('authApi.login.done', { user_id: response.data.data.id })

  return response.data.data
}

export async function getCurrentUser(): Promise<User> {
  appLogger.log('authApi.getCurrentUser.start')
  const response = await http.get<ResourceResponse<User>>('/auth/me')
  appLogger.log('authApi.getCurrentUser.done', { user_id: response.data.data.id })

  return response.data.data
}

export async function logout(): Promise<void> {
  appLogger.log('authApi.logout.start')
  await http.post('/auth/logout')
  appLogger.log('authApi.logout.done')
}
