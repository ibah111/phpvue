import { http } from '../../../shared/api/http'
import type {
  Organization,
  PaginatedResponse,
  ResourceResponse,
  Review,
} from '../../../shared/api/types'
import { appLogger } from '../../../shared/lib/logger'

export async function getOrganization(): Promise<Organization | null> {
  appLogger.log('organizationApi.getOrganization.start')
  const response = await http.get<ResourceResponse<Organization | null>>('/organization')
  appLogger.log('organizationApi.getOrganization.done', {
    organization_id: response.data.data?.id ?? null,
  })

  return response.data.data
}

export async function saveOrganization(url: string): Promise<Organization> {
  appLogger.log('organizationApi.saveOrganization.start', { url })
  const response = await http.post<ResourceResponse<Organization>>('/organization', { url })
  appLogger.log('organizationApi.saveOrganization.done', {
    organization_id: response.data.data.id,
    parsed_review_count: response.data.data.parsed_review_count,
  })

  return response.data.data
}

export async function getReviews(page: number): Promise<PaginatedResponse<Review>> {
  appLogger.log('organizationApi.getReviews.start', { page })
  const response = await http.get<PaginatedResponse<Review>>('/organization/reviews', {
    params: { page },
  })
  appLogger.log('organizationApi.getReviews.done', {
    page: response.data.meta.current_page,
    total: response.data.meta.total,
  })

  return response.data
}
