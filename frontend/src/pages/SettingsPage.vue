<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useSessionStore } from '../app/store/session'
import {
  getOrganization,
  getReviews,
  saveOrganization,
} from '../features/organization/api/organizationApi'
import type { Organization, PaginatedResponse, Review } from '../shared/api/types'
import { extractApiError } from '../shared/lib/apiError'
import { appLogger } from '../shared/lib/logger'

const session = useSessionStore()
const organization = ref<Organization | null>(null)
const reviews = ref<Review[]>([])
const pagination = ref<PaginatedResponse<Review>['meta'] | null>(null)
const form = reactive({ url: '' })
const isInitialLoading = ref(true)
const isSaving = ref(false)
const isReviewsLoading = ref(false)
const pageError = ref<string | null>(null)
const formError = ref<string | null>(null)

const pageNumbers = computed(() => {
  const meta = pagination.value
  if (!meta) {
    return []
  }

  const start = Math.max(1, meta.current_page - 2)
  const end = Math.min(meta.last_page, meta.current_page + 2)

  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function formatNumber(value: number | null | undefined) {
  return new Intl.NumberFormat('ru-RU').format(value ?? 0)
}

function formatDate(value: string | null) {
  if (!value) {
    return 'Нет даты'
  }

  return new Intl.DateTimeFormat('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  }).format(new Date(value))
}

async function loadOrganization() {
  appLogger.log('SettingsPage.loadOrganization.start')
  organization.value = await getOrganization()
  form.url = organization.value?.yandex_url ?? ''
  appLogger.log('SettingsPage.loadOrganization.done', {
    organization_id: organization.value?.id ?? null,
  })
}

async function loadReviews(page = 1) {
  if (!organization.value) {
    reviews.value = []
    pagination.value = null
    return
  }

  isReviewsLoading.value = true
  pageError.value = null
  appLogger.log('SettingsPage.loadReviews.start', { page })

  try {
    const response = await getReviews(page)
    reviews.value = response.data
    pagination.value = response.meta
    appLogger.log('SettingsPage.loadReviews.done', {
      page: response.meta.current_page,
      total: response.meta.total,
    })
  } catch (caughtError) {
    pageError.value = extractApiError(caughtError)
    appLogger.log('SettingsPage.loadReviews.error', { message: pageError.value })
  } finally {
    isReviewsLoading.value = false
  }
}

async function submit() {
  if (!form.url.trim() || isSaving.value) {
    return
  }

  isSaving.value = true
  formError.value = null
  pageError.value = null
  appLogger.log('SettingsPage.submit.start', { url: form.url })

  try {
    organization.value = await saveOrganization(form.url.trim())
    await loadReviews(1)
    appLogger.log('SettingsPage.submit.done', {
      organization_id: organization.value.id,
    })
  } catch (caughtError) {
    formError.value = extractApiError(caughtError)
    appLogger.log('SettingsPage.submit.error', { message: formError.value })
  } finally {
    isSaving.value = false
  }
}

async function changePage(page: number) {
  if (!pagination.value || page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) {
    return
  }

  await loadReviews(page)
}

async function logout() {
  await session.logout()
  window.location.assign('/login')
}

onMounted(async () => {
  isInitialLoading.value = true
  pageError.value = null

  try {
    await loadOrganization()
    await loadReviews(1)
  } catch (caughtError) {
    pageError.value = extractApiError(caughtError)
  } finally {
    isInitialLoading.value = false
  }
})
</script>

<template>
  <main class="app-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">Yandex Reviews</p>
        <h1>Настройки</h1>
      </div>
      <div class="topbar-user">
        <span>{{ session.state.user?.email }}</span>
        <button class="ghost-button" type="button" @click="logout">Выйти</button>
      </div>
    </header>

    <section class="settings-band">
      <form class="settings-form" @submit.prevent="submit">
        <label class="field wide-field">
          <span>Ссылка на карточку организации</span>
          <input
            v-model="form.url"
            autocomplete="off"
            name="url"
            placeholder="https://yandex.ru/maps/org/..."
            type="url"
          />
        </label>

        <button class="primary-button fixed-action" type="submit" :disabled="isSaving || !form.url.trim()">
          {{ isSaving ? 'Синхронизация...' : 'Сохранить' }}
        </button>
      </form>

      <p v-if="formError" class="alert">{{ formError }}</p>
    </section>

    <section v-if="organization" class="stats-grid" aria-label="Статистика организации">
      <div class="stat-item">
        <span>Компания</span>
        <strong>{{ organization.title || 'Без названия' }}</strong>
      </div>
      <div class="stat-item">
        <span>Средний рейтинг</span>
        <strong>{{ organization.average_rating ?? 'Нет' }}</strong>
      </div>
      <div class="stat-item">
        <span>Оценки</span>
        <strong>{{ formatNumber(organization.rating_count) }}</strong>
      </div>
      <div class="stat-item">
        <span>Отзывы</span>
        <strong>{{ formatNumber(organization.review_count) }}</strong>
      </div>
      <div class="stat-item">
        <span>Сохранено</span>
        <strong>{{ formatNumber(organization.parsed_review_count) }}</strong>
      </div>
    </section>

    <section class="reviews-section">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Отзывы</p>
          <h2>{{ organization?.address || 'Организация не подключена' }}</h2>
        </div>
        <span v-if="pagination" class="range-label">
          {{ pagination.from ?? 0 }}-{{ pagination.to ?? 0 }} из {{ formatNumber(pagination.total) }}
        </span>
      </div>

      <div v-if="isInitialLoading" class="state-line">Загрузка...</div>
      <div v-else-if="pageError" class="alert">{{ pageError }}</div>
      <div v-else-if="!organization" class="empty-state">Вставьте ссылку и сохраните настройки.</div>
      <div v-else class="reviews-table-wrap" :class="{ muted: isReviewsLoading }">
        <table class="reviews-table">
          <thead>
            <tr>
              <th>Автор</th>
              <th>Дата</th>
              <th>Оценка</th>
              <th>Текст</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="review in reviews" :key="review.yandex_review_id">
              <td>{{ review.author.name || 'Аноним' }}</td>
              <td>{{ formatDate(review.date) }}</td>
              <td>
                <span class="rating-pill">{{ review.rating ?? '-' }}</span>
              </td>
              <td class="review-text">{{ review.text || 'Без текста' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <nav v-if="pagination && pagination.last_page > 1" class="pagination" aria-label="Навигация отзывов">
        <button type="button" :disabled="pagination.current_page === 1" @click="changePage(pagination.current_page - 1)">
          Назад
        </button>
        <button
          v-for="page in pageNumbers"
          :key="page"
          type="button"
          :class="{ active: page === pagination.current_page }"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          type="button"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          Вперед
        </button>
      </nav>
    </section>
  </main>
</template>
