<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next'
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '../app/store/session'
import { extractApiError } from '../shared/lib/apiError'
import { appLogger } from '../shared/lib/logger'

const router = useRouter()
const session = useSessionStore()
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const isPasswordVisible = ref(false)
const form = reactive({
  email: 'demo@example.com',
  password: 'ReviewsDemo!2026#7pQz',
})

const canSubmit = computed(() => form.email.trim() !== '' && form.password !== '' && !isSubmitting.value)
const passwordInputType = computed(() => (isPasswordVisible.value ? 'text' : 'password'))

function togglePasswordVisibility() {
  isPasswordVisible.value = !isPasswordVisible.value
  appLogger.log('LoginPage.togglePasswordVisibility', {
    is_visible: isPasswordVisible.value,
  })
}

async function submit() {
  if (!canSubmit.value) {
    return
  }

  isSubmitting.value = true
  error.value = null
  appLogger.log('LoginPage.submit.start', { email: form.email })

  try {
    await session.login(form.email, form.password)
    await router.push('/settings')
    appLogger.log('LoginPage.submit.done')
  } catch (caughtError) {
    error.value = extractApiError(caughtError)
    appLogger.log('LoginPage.submit.error', { message: error.value })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <main class="auth-page">
    <form class="auth-form" @submit.prevent="submit">
      <div class="auth-heading">
        <p class="eyebrow">Yandex Reviews</p>
        <h1>Вход</h1>
      </div>

      <label class="field">
        <span>Email</span>
        <input v-model="form.email" autocomplete="email" name="email" type="email" />
      </label>

      <label class="field">
        <span>Пароль</span>
        <span class="password-input">
          <input v-model="form.password" autocomplete="current-password" name="password" :type="passwordInputType" />
          <button
            class="password-toggle"
            type="button"
            :aria-label="isPasswordVisible ? 'Скрыть пароль' : 'Показать пароль'"
            :aria-pressed="isPasswordVisible"
            :title="isPasswordVisible ? 'Скрыть пароль' : 'Показать пароль'"
            @click="togglePasswordVisibility"
          >
            <EyeOff v-if="isPasswordVisible" :size="18" stroke-width="2.2" aria-hidden="true" />
            <Eye v-else :size="18" stroke-width="2.2" aria-hidden="true" />
          </button>
        </span>
      </label>

      <p v-if="error" class="alert">{{ error }}</p>

      <button class="primary-button" type="submit" :disabled="!canSubmit">
        {{ isSubmitting ? 'Входим...' : 'Войти' }}
      </button>
    </form>
  </main>
</template>
