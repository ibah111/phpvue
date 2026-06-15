export interface User {
  id: number
  name: string
  email: string
}

export interface Organization {
  id: number
  yandex_business_id: string
  yandex_url: string
  title: string | null
  address: string | null
  average_rating: number | null
  rating_count: number
  review_count: number
  parsed_review_count: number
  sync_status: string
  sync_error: string | null
  last_synced_at: string | null
  created_at: string | null
  updated_at: string | null
}

export interface Review {
  id: number
  yandex_review_id: string
  author: {
    name: string | null
    public_id: string | null
  }
  date: string | null
  text: string | null
  rating: number | null
}

export interface ResourceResponse<T> {
  data: T
}

export interface PaginatedResponse<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    path: string
    per_page: number
    to: number | null
    total: number
  }
}

export interface ApiErrorResponse {
  message?: string
  errors?: Record<string, string[]>
}
