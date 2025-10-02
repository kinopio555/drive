<template>
  <v-main>
    <v-container class="py-8">
      <v-row justify="center">
        <v-col cols="12" lg="10">
          <v-card elevation="4" class="pa-6">
            <div class="text-center mb-6">
              <v-icon size="56" color="primary" class="mb-4">mdi-view-dashboard</v-icon>
              <h1 class="text-h4 font-weight-bold mb-2">ダッシュボード</h1>
              <p class="text-body-1 mb-4">
                {{ greetingMessage }}
              </p>
              <v-alert
                v-if="logoutError"
                type="error"
                variant="tonal"
                class="mb-4"
                density="comfortable"
              >
                {{ logoutError }}
              </v-alert>
              <div class="d-flex flex-column flex-sm-row ga-4 justify-center">
                <v-btn color="primary" size="large" to="/">
                  ホームへ戻る
                </v-btn>
                <v-btn
                  color="error"
                  variant="outlined"
                  size="large"
                  :loading="isLoggingOut"
                  :disabled="isLoggingOut"
                  @click="logout"
                >
                  ログアウト
                </v-btn>
              </div>
            </div>

            <v-divider class="my-6" />

            <section>
              <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between mb-4 ga-4">
                <div>
                  <h2 class="text-h5 font-weight-bold mb-1">近くのレストラン</h2>
                  <p class="text-body-2 text-medium-emphasis">
                    現在地周辺のレストラン情報を表示します。
                  </p>
                </div>
                <v-btn
                  color="primary"
                  variant="tonal"
                  prepend-icon="mdi-refresh"
                  :loading="restaurantsLoading"
                  :disabled="restaurantsLoading"
                  @click="refreshRestaurants"
                >
                  最新の情報を取得
                </v-btn>
              </div>

              <v-alert
                v-if="restaurantsError"
                type="error"
                variant="tonal"
                class="mb-4"
                density="comfortable"
              >
                {{ restaurantsError }}
              </v-alert>

              <v-progress-linear
                v-if="restaurantsLoading"
                indeterminate
                color="primary"
                class="mb-6"
              />

              <div v-else>
                <div v-if="restaurants.length" class="d-flex flex-column ga-4">
                  <v-card
                    v-for="restaurant in restaurants"
                    :key="restaurant.id"
                    variant="outlined"
                    class="pa-4"
                  >
                    <div class="d-flex flex-column flex-md-row justify-space-between ga-4">
                      <div class="flex-grow-1">
                        <div class="d-flex align-center ga-2 mb-2">
                          <v-icon color="primary">mdi-silverware-fork-knife</v-icon>
                          <h3 class="text-h6 font-weight-bold mb-0">
                            {{ restaurant.name }}
                          </h3>
                        </div>

                        <div class="text-body-2 text-medium-emphasis mb-2">
                          {{ restaurant.address || '住所情報がありません' }}
                        </div>

                        <div class="d-flex flex-wrap align-center ga-2 text-body-2">
                          <div v-if="restaurant.categories.length" class="d-flex flex-wrap ga-2">
                            <v-chip
                              v-for="category in restaurant.categories"
                              :key="`${restaurant.id}-${category}`"
                              size="small"
                              variant="tonal"
                              color="secondary"
                            >
                              {{ category }}
                            </v-chip>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex flex-column justify-center align-center ga-2 text-center">
                        <div v-if="restaurant.rating !== undefined" class="text-h6 font-weight-bold">
                          {{ restaurant.rating.toFixed(1) }}
                        </div>
                        <v-rating
                          v-if="restaurant.rating !== undefined"
                          :model-value="restaurant.rating"
                          color="amber"
                          half-increments
                          readonly
                          density="comfortable"
                        />
                        <div v-if="restaurant.reviewCount !== undefined" class="text-caption text-medium-emphasis">
                          {{ restaurant.reviewCount }} 件のレビュー
                        </div>
                        <div v-if="restaurant.price" class="text-body-2">
                          {{ restaurant.price }}
                        </div>
                        <div v-if="restaurant.distanceText" class="text-body-2 text-medium-emphasis">
                          {{ restaurant.distanceText }}
                        </div>
                        <v-chip
                          v-if="restaurant.isOpen !== null"
                          :color="restaurant.isOpen ? 'success' : 'error'"
                          size="small"
                          variant="flat"
                        >
                          {{ restaurant.isOpen ? '営業中' : '営業時間外' }}
                        </v-chip>
                        <v-btn
                          v-if="restaurant.infoUrl"
                          :href="restaurant.infoUrl"
                          target="_blank"
                          rel="noopener"
                          variant="text"
                          append-icon="mdi-open-in-new"
                          size="small"
                        >
                          詳細を見る
                        </v-btn>
                      </div>
                    </div>
                  </v-card>
                </div>

                <v-alert
                  v-else
                  type="info"
                  variant="tonal"
                  density="comfortable"
                >
                  レストラン情報が見つかりませんでした。
                </v-alert>
              </div>
            </section>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<script setup lang="ts">
import { $fetch, FetchError } from 'ofetch'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from '#imports'
import { useCookie } from '#app'

const router = useRouter()

const apiBaseUrl = 'http://localhost:8080'
const isLoginEndpoint = `${apiBaseUrl}/is-login`
const userEndpoint = `${apiBaseUrl}/get-user`
const logoutEndpoint = `${apiBaseUrl}/logout`
const csrfEndpoint = `${apiBaseUrl}/sanctum/csrf-cookie`
const restaurantsEndpoint = `${apiBaseUrl}/api/restaurants-nearby`

type NormalizedRestaurant = {
  id: string
  name: string
  address: string
  rating?: number
  reviewCount?: number
  price?: string
  categories: string[]
  distanceText?: string
  isOpen: boolean | null
  infoUrl?: string
}

const userName = ref<string>('')
const loading = ref(true)
const isLoggingOut = ref(false)
const logoutError = ref('')
const restaurants = ref<NormalizedRestaurant[]>([])
const restaurantsLoading = ref(false)
const restaurantsError = ref('')

const greetingMessage = computed(() => {
  if (loading.value) {
    return '読み込み中です...'
  }

  if (userName.value) {
    return `${userName.value} さん、ログインしています。`
  }

  return 'ユーザー情報を取得できませんでした。'
})

const coerceToBoolean = (value: unknown) => {
  if (typeof value === 'boolean') {
    return value
  }

  if (typeof value === 'number') {
    return value === 1
  }

  if (typeof value === 'string') {
    return value === 'true' || value === '1'
  }

  if (value && typeof value === 'object') {
    if ('loggedIn' in value) {
      return coerceToBoolean((value as { loggedIn?: unknown }).loggedIn)
    }

    if ('isLogin' in value) {
      return coerceToBoolean((value as { isLogin?: unknown }).isLogin)
    }
  }

  return false
}

const readXsrfToken = () => {
  const tokenCookie = useCookie<string | null>('XSRF-TOKEN')
  if (tokenCookie.value) {
    return decodeURIComponent(tokenCookie.value)
  }

  if (typeof document !== 'undefined') {
    const match = document.cookie.split(';').map((entry) => entry.trim()).find((entry) => entry.startsWith('XSRF-TOKEN='))
    if (match) {
      return decodeURIComponent(match.split('=').slice(1).join('='))
    }
  }

  return ''
}

const parseErrorMessage = (error: unknown) => {
  if (typeof error === 'string') {
    return error
  }

  if (error instanceof Error) {
    return error.message
  }

  if (typeof error === 'object' && error) {
    const maybeMessage = (error as { data?: { message?: string; errors?: Record<string, string[]> }; statusMessage?: string }).data?.message
    if (maybeMessage) {
      return maybeMessage
    }

    const maybeErrors = (error as { data?: { errors?: Record<string, string[]> } }).data?.errors
    if (maybeErrors) {
      const firstEntry = Object.values(maybeErrors)[0]
      if (firstEntry?.length) {
        return firstEntry[0]
      }
    }

    if ((error as { statusMessage?: string }).statusMessage) {
      return (error as { statusMessage: string }).statusMessage
    }
  }

  return '処理に失敗しました。時間をおいて再度お試しください。'
}

const extractNumeric = (value: unknown): number | undefined => {
  if (typeof value === 'number' && !Number.isNaN(value)) {
    return value
  }

  if (typeof value === 'string') {
    const parsed = Number.parseFloat(value)
    if (!Number.isNaN(parsed)) {
      return parsed
    }
  }

  return undefined
}

const extractString = (value: unknown): string | undefined => {
  if (typeof value === 'string') {
    const trimmed = value.trim()
    return trimmed.length ? trimmed : undefined
  }

  if (typeof value === 'number' && Number.isFinite(value)) {
    return value.toString()
  }

  return undefined
}

const extractStringArray = (value: unknown): string[] => {
  if (Array.isArray(value)) {
    return value
      .map((item) => extractString(item))
      .filter((item): item is string => Boolean(item))
  }

  const single = extractString(value)
  if (single) {
    return single.split(',').map((item) => item.trim()).filter(Boolean)
  }

  return []
}

const buildDistanceText = (value?: number) => {
  if (value === undefined) {
    return undefined
  }

  if (value >= 1) {
    return `${value.toFixed(1)} km`
  }

  return `${Math.round(value * 1000)} m`
}

const getOriginHeader = () => {
  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin
  }

  return 'http://localhost:8080'
}

const normalizeRestaurants = (payload: unknown): NormalizedRestaurant[] => {
  const candidates = (() => {
    if (Array.isArray(payload)) {
      return payload
    }

    if (payload && typeof payload === 'object') {
      if (Array.isArray((payload as { data?: unknown[] }).data)) {
        return (payload as { data: unknown[] }).data
      }

      if (Array.isArray((payload as { restaurants?: unknown[] }).restaurants)) {
        return (payload as { restaurants: unknown[] }).restaurants
      }

      if (Array.isArray((payload as { results?: unknown[] }).results)) {
        return (payload as { results: unknown[] }).results
      }
    }

    return []
  })()

  return candidates
    .map((item, index) => {
      if (!item || typeof item !== 'object') {
        return undefined
      }

      const record = item as Record<string, unknown>

      const id =
        extractString(record.id) ||
        extractString(record.place_id) ||
        extractString(record.uuid) ||
        `${index}`

      const name = extractString(record.name) || extractString(record.title) || '名称不明'
      const address =
        extractString(record.address) ||
        extractString(record.formatted_address) ||
        extractString(record.vicinity) ||
        extractString(record.location) ||
        '住所情報なし'

      const rating = extractNumeric(record.rating)
      const reviewCount = extractNumeric(record.user_ratings_total ?? record.review_count)

      const price =
        extractString(record.price)
        || extractString(record.price_level)
        || extractString(record.priceRange)

      const categories = extractStringArray(
        record.categories ?? record.category ?? record.types ?? record.cuisines,
      )

      const distanceMeters = extractNumeric(record.distanceMeters ?? record.distance_meters)
      const distanceKilometers = extractNumeric(record.distanceKm ?? record.distance_km ?? record.distance)

      const distance = distanceKilometers ?? (distanceMeters !== undefined ? distanceMeters / 1000 : undefined)

      const openNowRaw = record.open_now ?? record.is_open ?? record.openNow ?? record.isOpen
      const isOpen = typeof openNowRaw === 'boolean' ? openNowRaw : null

      const infoUrl =
        extractString(record.website)
        || extractString(record.url)
        || extractString(record.reserve_url)

      return {
        id,
        name,
        address,
        rating,
        reviewCount,
        price,
        categories,
        distanceText: buildDistanceText(distance),
        isOpen,
        infoUrl,
      }
    })
    .filter((item): item is NormalizedRestaurant => Boolean(item))
}

const loadUser = async (): Promise<boolean> => {
  try {
    const loginStatus = await $fetch(isLoginEndpoint, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      throwOnHTTPError: false,
    })

    if (!coerceToBoolean(loginStatus)) {
      await router.replace('/login')
      return false
    }

    const user = await $fetch<{ name?: string }>(userEndpoint, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    if (user?.name) {
      userName.value = user.name
    }

    return true
  } catch (error) {
    console.error('ユーザー情報の取得に失敗しました', error)
    return false
  } finally {
    loading.value = false
  }
}

const loadRestaurants = async () => {
  if (restaurantsLoading.value) {
    return
  }

  restaurantsLoading.value = true
  restaurantsError.value = ''

  try {
    const origin = getOriginHeader()
    console.log('origin', origin)
        await $fetch(csrfEndpoint, {
      method: 'GET',
      credentials: 'include',
    })

    const xsrfToken = readXsrfToken()
    if (!xsrfToken) {
      throw new Error('CSRFトークンの取得に失敗しました。')
    }

    const response = await $fetch<unknown>(restaurantsEndpoint, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        Origin: origin,
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'include',
    })

    const normalized = normalizeRestaurants(response)
    restaurants.value = normalized
  } catch (error) {
    console.error('レストラン情報の取得に失敗しました', error)
    restaurantsError.value = parseErrorMessage(error)
  } finally {
    restaurantsLoading.value = false
  }
}

const refreshRestaurants = () => {
  restaurants.value = []
  loadRestaurants()
}

const logout = async () => {
  if (isLoggingOut.value) {
    return
  }

  isLoggingOut.value = true
  logoutError.value = ''

  try {
    await $fetch(csrfEndpoint, {
      method: 'GET',
      credentials: 'include',
    })

    const xsrfToken = readXsrfToken()
    if (!xsrfToken) {
      throw new Error('CSRFトークンの取得に失敗しました。')
    }

    await $fetch(logoutEndpoint, {
      method: 'POST',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrfToken,
      },
    })

    await router.replace('/login')
  } catch (error: unknown) {
    if (error instanceof FetchError && error.response?.status === 302) {
      await router.replace('/login')
      return
    }

    logoutError.value = parseErrorMessage(error)
  } finally {
    isLoggingOut.value = false
  }
}

onMounted(async () => {
  const isLoggedIn = await loadUser()
  if (isLoggedIn) {
    await loadRestaurants()
  }
})
</script>
