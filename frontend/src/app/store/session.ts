import { reactive } from 'vue'
import { getCurrentUser, login as loginRequest, logout as logoutRequest } from '../../features/auth/api/authApi'
import type { User } from '../../shared/api/types'
import { appLogger } from '../../shared/lib/logger'

interface SessionState {
  user: User | null
  isBootstrapping: boolean
  error: string | null
}

const state = reactive<SessionState>({
  user: null,
  isBootstrapping: false,
  error: null,
})

let bootstrapPromise: Promise<void> | null = null

export function useSessionStore() {
  async function bootstrap(): Promise<void> {
    if (bootstrapPromise) {
      return bootstrapPromise
    }

    state.isBootstrapping = true
    bootstrapPromise = getCurrentUser()
      .then((user) => {
        state.user = user
        state.error = null
        appLogger.log('session.bootstrap.authenticated', { user_id: user.id })
      })
      .catch(() => {
        state.user = null
        appLogger.log('session.bootstrap.guest')
      })
      .finally(() => {
        state.isBootstrapping = false
      })

    return bootstrapPromise
  }

  async function login(email: string, password: string): Promise<void> {
    state.error = null
    const user = await loginRequest({ email, password })
    state.user = user
    appLogger.log('session.login.done', { user_id: user.id })
  }

  async function logout(): Promise<void> {
    await logoutRequest()
    state.user = null
    appLogger.log('session.logout.done')
  }

  return {
    state,
    bootstrap,
    login,
    logout,
  }
}
