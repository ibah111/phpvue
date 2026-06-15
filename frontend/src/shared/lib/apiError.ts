import { AxiosError } from 'axios'
import type { ApiErrorResponse } from '../api/types'

export function extractApiError(error: unknown): string {
  if (error instanceof AxiosError) {
    const data = error.response?.data as ApiErrorResponse | undefined
    const firstFieldError = data?.errors ? Object.values(data.errors)[0]?.[0] : null

    return firstFieldError ?? data?.message ?? error.message
  }

  if (error instanceof Error) {
    return error.message
  }

  return 'Неизвестная ошибка.'
}
