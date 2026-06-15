import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../../pages/LoginPage.vue'
import SettingsPage from '../../pages/SettingsPage.vue'
import { useSessionStore } from '../store/session'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/settings' },
    { path: '/login', component: LoginPage, meta: { guestOnly: true } },
    { path: '/settings', component: SettingsPage, meta: { requiresAuth: true } },
  ],
})

router.beforeEach(async (to) => {
  const session = useSessionStore()
  await session.bootstrap()

  if (to.meta.requiresAuth && !session.state.user) {
    return '/login'
  }

  if (to.meta.guestOnly && session.state.user) {
    return '/settings'
  }

  return true
})
