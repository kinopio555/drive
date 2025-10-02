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
                  <h2 class="text-h5 font-weight-bold mb-1">保存した経路とレストラン</h2>
                  <p class="text-body-2 text-medium-emphasis">
                    出発地から目的地までの経路周辺で見つかった飲食店を表示します。
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
                <div v-if="routes.length" class="d-flex flex-column ga-4">
                  <v-card
                    v-for="route in routes"
                    :key="route.id"
                    variant="outlined"
                    class="pa-4"
                  >
                    <div class="d-flex flex-column flex-lg-row ga-6">
                      <div class="flex-grow-1">
                        <div class="d-flex flex-column flex-sm-row ga-4">
                          <div class="d-flex align-center ga-3 flex-grow-1">
                            <v-avatar color="primary" variant="tonal" size="48">
                              <v-icon size="28">mdi-map-marker</v-icon>
                            </v-avatar>
                            <div>
                              <div class="text-overline text-medium-emphasis">出発地</div>
                              <div class="text-body-1 font-weight-medium">
                                {{ route.origin }}
                              </div>
                            </div>
                          </div>
                          <div class="d-flex align-center ga-3 flex-grow-1">
                            <v-avatar color="secondary" variant="tonal" size="48">
                              <v-icon size="28">mdi-flag-checkered</v-icon>
                            </v-avatar>
                            <div>
                              <div class="text-overline text-medium-emphasis">目的地</div>
                              <div class="text-body-1 font-weight-medium">
                                {{ route.destination }}
                              </div>
                            </div>
                          </div>
                        </div>

                        <v-divider class="my-4" />

                        <div>
                          <div class="d-flex align-center justify-space-between mb-3">
                            <div class="text-subtitle-2 text-medium-emphasis">
                              経路周辺の飲食店
                            </div>
                            <v-chip size="small" color="primary" variant="tonal">
                              {{ route.restaurantNames.length }} 店舗
                            </v-chip>
                          </div>

                          <v-list density="compact" lines="two" class="bg-transparent">
                            <v-list-item
                              v-for="(name, index) in route.restaurantNames"
                              :key="`${route.id}-${name}`"
                              class="rounded"
                            >
                              <template #prepend>
                                <v-avatar size="28" color="primary" variant="tonal">
                                  <span class="text-caption font-weight-semibold">{{ index + 1 }}</span>
                                </v-avatar>
                              </template>
                              <v-list-item-title class="text-body-1 font-weight-medium">
                                {{ name }}
                              </v-list-item-title>
                              <v-list-item-subtitle class="text-body-2 text-medium-emphasis">
                                経路沿いのおすすめスポット
                              </v-list-item-subtitle>
                            </v-list-item>
                          </v-list>

                          <div v-if="!route.restaurantNames.length" class="text-body-2 text-medium-emphasis">
                            レストラン候補が登録されていません。
                          </div>
                        </div>
                      </div>

                      <v-divider vertical class="hidden-lg-and-down" />

                      <div class="d-flex flex-column ga-3 align-start justify-start">
                        <div class="text-subtitle-2 text-medium-emphasis">メモ</div>
                        <p class="text-body-2 mb-0">
                          出発地と目的地の組み合わせごとに保存された飲食店候補です。ルートを更新すると最新のリストが反映されます。
                        </p>
                        <v-btn
                          variant="text"
                          size="small"
                          color="primary"
                          prepend-icon="mdi-map-marker-path"
                          @click="refreshRestaurants"
                        >
                          この経路を更新
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
                  保存された経路がまだありません。
                </v-alert>
              </div>
            </section>

            <v-divider class="my-8" />

            <section>
              <div class="d-flex flex-column flex-lg-row align-lg-center justify-space-between mb-4 ga-4">
                <div>
                  <h2 class="text-h5 font-weight-bold mb-1">ルートポリラインを取得</h2>
                  <p class="text-body-2 text-medium-emphasis">
                    origin / destination と任意のヘッダーを指定して、Routes API のレスポンスを確認できます。
                  </p>
                </div>
              </div>

              <v-form class="mb-6" @submit.prevent="fetchPolyline">
                <v-row class="ga-4">
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="polylineForm.origin"
                      label="出発地 (origin)"
                      placeholder="例: 東京駅"
                      prepend-inner-icon="mdi-map-marker"
                      clearable
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="polylineForm.destination"
                      label="目的地 (destination)"
                      placeholder="例: 京都駅"
                      prepend-inner-icon="mdi-flag-checkered"
                      clearable
                      required
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="polylineForm.headersText"
                      label="追加リクエストヘッダー"
                      hint='1行につき1ヘッダーを "Header-Name: value" 形式で入力します。'
                      persistent-hint
                      rows="4"
                      auto-grow
                      prepend-inner-icon="mdi-form-textarea"
                    />
                    <div class="d-flex flex-wrap ga-2 mt-2">
                      <v-btn
                        type="submit"
                        color="primary"
                        :loading="polylineLoading"
                        :disabled="polylineLoading"
                        prepend-icon="mdi-download"
                      >
                        ポリラインを取得
                      </v-btn>
                      <v-btn
                        type="button"
                        variant="text"
                        color="secondary"
                        prepend-icon="mdi-sync"
                        @click="clearPolylineHeaders"
                      >
                        ヘッダー入力をクリア
                      </v-btn>
                    </div>
                  </v-col>
                </v-row>
              </v-form>

              <v-alert
                v-if="invalidHeaderLines.length"
                type="warning"
                variant="tonal"
                class="mb-4"
                density="comfortable"
              >
                入力形式が正しくないヘッダー行: {{ invalidHeaderLines.join(', ') }}
              </v-alert>

              <v-alert
                v-if="polylineError"
                type="error"
                variant="tonal"
                class="mb-4"
                density="comfortable"
              >
                {{ polylineError }}
              </v-alert>

              <v-progress-linear
                v-if="polylineLoading"
                indeterminate
                color="primary"
                class="mb-6"
              />

              <div v-else-if="hasPolylineResult" class="d-flex flex-column ga-4">
                <v-card variant="tonal" color="primary" class="pa-4">
                  <div class="text-subtitle-1 font-weight-medium mb-2">
                    エンコード済みポリライン
                  </div>
                  <v-sheet class="bg-primary-lighten-5 pa-3 rounded text-body-2 font-mono overflow-auto">
                    {{ polylineResult.polyline || '取得されたポリラインがありません。' }}
                  </v-sheet>
                  <div class="d-flex flex-wrap align-center ga-2 mt-3">
                    <span class="text-body-2 text-medium-emphasis">経路周辺の飲食店</span>
                    <template v-if="polylineResult.restaurantNames.length">
                      <v-chip
                        v-for="name in polylineResult.restaurantNames"
                        :key="name"
                        color="secondary"
                        variant="tonal"
                        size="small"
                      >
                        {{ name }}
                      </v-chip>
                    </template>
                    <span v-else class="text-body-2 text-medium-emphasis">
                      レストラン候補が見つかりませんでした。
                    </span>
                  </div>
                </v-card>

                <v-expansion-panels variant="popout" multiple>
                  <v-expansion-panel
                    v-for="(sample, index) in polylineResult.samples"
                    :key="sample.id"
                  >
                    <v-expansion-panel-title>
                      サンプル {{ index + 1 }} ・ {{ formatCoordinate(sample.coordinate) }}
                      <template #actions>
                        <v-chip size="small" color="primary" variant="tonal">
                          {{ sample.restaurants.length }} 店舗
                        </v-chip>
                      </template>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <div v-if="sample.restaurants.length" class="d-flex flex-column ga-3">
                        <v-card
                          v-for="restaurant in sample.restaurants"
                          :key="restaurant.id"
                          variant="outlined"
                          class="pa-3"
                        >
                          <div class="d-flex flex-column flex-sm-row align-sm-center justify-space-between ga-3">
                            <div>
                              <div class="text-body-1 font-weight-medium">
                                {{ restaurant.name }}
                              </div>
                              <div class="text-body-2 text-medium-emphasis">
                                {{ formatRestaurantSubtitle(restaurant) }}
                              </div>
                            </div>
                            <div class="text-body-2 text-medium-emphasis text-sm-end">
                              {{ formatCoordinate(restaurant.coordinate) }}
                            </div>
                          </div>
                        </v-card>
                      </div>
                      <div v-else class="text-body-2 text-medium-emphasis">
                        この地点付近の飲食店は見つかりませんでした。
                      </div>
                    </v-expansion-panel-text>
                  </v-expansion-panel>

                  <v-expansion-panel v-if="!polylineResult.samples.length">
                    <v-expansion-panel-title>
                      サンプル地点がありません
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      経路サンプルが取得できませんでした。入力した地点やヘッダーを変更して再試行してください。
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from '#imports'
import { useCookie } from '#app'

const router = useRouter()

const apiBaseUrl = 'http://localhost:8080'
const isLoginEndpoint = `${apiBaseUrl}/is-login`
const userEndpoint = `${apiBaseUrl}/get-user`
const logoutEndpoint = `${apiBaseUrl}/logout`
const csrfEndpoint = `${apiBaseUrl}/sanctum/csrf-cookie`
const restaurantsEndpoint = `${apiBaseUrl}/api/restaurants-nearby`
const polylineEndpoint = `${apiBaseUrl}/api/routes/polyline`

type RouteWithRestaurants = {
  id: string
  origin: string
  destination: string
  restaurantNames: string[]
}

type PolylineCoordinate = {
  latitude: number | null
  longitude: number | null
}

type PolylineSampleRestaurant = {
  id: string
  name: string
  rating?: number
  reviewCount?: number
  vicinity?: string
  coordinate: PolylineCoordinate | null
}

type PolylineSample = {
  id: string
  coordinate: PolylineCoordinate | null
  restaurants: PolylineSampleRestaurant[]
}

type PolylineResult = {
  polyline: string
  restaurantNames: string[]
  samples: PolylineSample[]
}

const userName = ref<string>('')
const loading = ref(true)
const isLoggingOut = ref(false)
const logoutError = ref('')
const routes = ref<RouteWithRestaurants[]>([])
const restaurantsLoading = ref(false)
const restaurantsError = ref('')

const polylineForm = reactive({
  origin: '',
  destination: '',
  headersText: '',
})
const polylineLoading = ref(false)
const polylineError = ref('')
const polylineResult = ref<PolylineResult | null>(null)
const invalidHeaderLines = ref<string[]>([])

const greetingMessage = computed(() => {
  if (loading.value) {
    return '読み込み中です...'
  }

  if (userName.value) {
    return `${userName.value} さん、ログインしています。`
  }

  return 'ユーザー情報を取得できませんでした。'
})

const hasPolylineResult = computed(() => polylineResult.value !== null)

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

const getOriginHeader = () => {
  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin
  }

  return 'http://localhost:3000'
}

const readXsrfToken = () => {
  const tokenCookie = useCookie<string | null>('XSRF-TOKEN')
  if (tokenCookie.value) {
    return decodeURIComponent(tokenCookie.value)
  }

  if (typeof document !== 'undefined') {
    const match = document.cookie
      .split(';')
      .map((entry) => entry.trim())
      .find((entry) => entry.startsWith('XSRF-TOKEN='))

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

const extractNumber = (value: unknown): number | undefined => {
  if (typeof value === 'number' && Number.isFinite(value)) {
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

const extractCoordinate = (value: unknown): PolylineCoordinate | null => {
  if (value && typeof value === 'object') {
    const record = value as Record<string, unknown>
    const latitude = extractNumber(record.latitude ?? record.lat)
    const longitude = extractNumber(record.longitude ?? record.lng ?? record.lon)

    return {
      latitude: latitude ?? null,
      longitude: longitude ?? null,
    }
  }

  return null
}

const normalizeRoutes = (payload: unknown): RouteWithRestaurants[] => {
  const candidates = (() => {
    if (Array.isArray(payload)) {
      return payload
    }

    if (payload && typeof payload === 'object' && Array.isArray((payload as { data?: unknown[] }).data)) {
      return (payload as { data: unknown[] }).data
    }

    return []
  })()

  return candidates
    .map((item, index) => {
      if (!item || typeof item !== 'object') {
        return undefined
      }

      const record = item as Record<string, unknown>

      const origin = extractString(record.origin) ?? '出発地情報なし'
      const destination = extractString(record.destination) ?? '目的地情報なし'
      const restaurantNames = extractStringArray(record.restaurants_names ?? record.restaurantNames)

      return {
        id: extractString(record.id) ?? `${origin}-${destination}-${index}`,
        origin,
        destination,
        restaurantNames,
      }
    })
    .filter((item): item is RouteWithRestaurants => Boolean(item))
}

const normalizePolylineResponse = (payload: unknown): PolylineResult => {
  if (!payload || typeof payload !== 'object') {
    return {
      polyline: '',
      restaurantNames: [],
      samples: [],
    }
  }

  const record = payload as Record<string, unknown>
  const polyline = extractString(record.polyline) ?? ''
  const restaurantNames = Array.from(new Set(extractStringArray(record.restaurants_names ?? record.restaurantNames)))

  const samples = Array.isArray(record.samples)
    ? (record.samples as unknown[])
        .map((sample, index) => {
          if (!sample || typeof sample !== 'object') {
            return undefined
          }

          const sampleRecord = sample as Record<string, unknown>
          const coordinate = extractCoordinate(sampleRecord.coordinate)

          const restaurants = Array.isArray(sampleRecord.restaurants)
            ? (sampleRecord.restaurants as unknown[])
                .map((restaurant, restaurantIndex) => {
                  if (!restaurant || typeof restaurant !== 'object') {
                    return undefined
                  }

                  const restaurantRecord = restaurant as Record<string, unknown>

                  const restaurantId =
                    extractString(restaurantRecord.place_id ?? restaurantRecord.id)
                    ?? `${index}-${restaurantIndex}`

                  return {
                    id: restaurantId,
                    name: extractString(restaurantRecord.name) ?? '名称不明',
                    rating: extractNumber(restaurantRecord.rating),
                    reviewCount: extractNumber(restaurantRecord.user_ratings_total ?? restaurantRecord.reviewCount),
                    vicinity: extractString(restaurantRecord.vicinity ?? restaurantRecord.address),
                    coordinate: extractCoordinate(restaurantRecord.location),
                  }
                })
                .filter((item): item is PolylineSampleRestaurant => Boolean(item))
            : []

          return {
            id: extractString(sampleRecord.id) ?? `sample-${index}`,
            coordinate,
            restaurants,
          }
        })
        .filter((item): item is PolylineSample => Boolean(item))
    : []

  return {
    polyline,
    restaurantNames,
    samples,
  }
}

const ensureCsrfCookie = async () => {
  if (readXsrfToken()) {
    return
  }

  try {
    await $fetch(csrfEndpoint, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        Origin: getOriginHeader(),
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
  } catch (error) {
    console.warn('CSRFクッキーの取得に失敗しました', error)
  }
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

const loadRoutes = async () => {
  if (restaurantsLoading.value) {
    return
  }

  restaurantsLoading.value = true
  restaurantsError.value = ''

  try {
    await ensureCsrfCookie()

    const origin = getOriginHeader()
    const xsrfToken = readXsrfToken()

    const response = await $fetch<unknown>(restaurantsEndpoint, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        Origin: origin,
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      },
      credentials: 'include',
    })

    routes.value = normalizeRoutes(response)
  } catch (error) {
    console.error('レストラン情報の取得に失敗しました', error)

    if (error instanceof FetchError && error.response?.status === 401) {
      routes.value = []
      restaurantsError.value = 'セッションの有効期限が切れています。もう一度ログインしてください。'
      await router.replace('/login')
      return
    }

    restaurantsError.value = parseErrorMessage(error)
  } finally {
    restaurantsLoading.value = false
  }
}

const refreshRestaurants = () => {
  routes.value = []
  loadRoutes()
}

const parseHeadersInput = (input: string) => {
  const headers: Record<string, string> = {}
  const invalidLinesLocal: string[] = []

  input
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.length > 0)
    .forEach((line) => {
      const separatorIndex = line.indexOf(':')

      if (separatorIndex === -1) {
        invalidLinesLocal.push(line)
        return
      }

      const name = line.slice(0, separatorIndex).trim()
      const value = line.slice(separatorIndex + 1).trim()

      if (!name || !value) {
        invalidLinesLocal.push(line)
        return
      }

      headers[name] = value
    })

  return { headers, invalidLines: invalidLinesLocal }
}

const fetchPolyline = async () => {
  if (polylineLoading.value) {
    return
  }

  polylineLoading.value = true
  polylineError.value = ''
  polylineResult.value = null
  invalidHeaderLines.value = []

  const originInput = polylineForm.origin.trim()
  const destinationInput = polylineForm.destination.trim()

  if (!originInput || !destinationInput) {
    polylineLoading.value = false
    polylineError.value = '出発地と目的地を入力してください。'
    return
  }

  try {
    await ensureCsrfCookie()

    const customHeadersResult = parseHeadersInput(polylineForm.headersText)

    if (customHeadersResult.invalidLines.length) {
      invalidHeaderLines.value = customHeadersResult.invalidLines
      throw new Error('ヘッダーの形式が正しくありません。"Header-Name: value" の形式で入力してください。')
    }

    const origin = getOriginHeader()
    const xsrfToken = readXsrfToken()

    const baseHeaders: Record<string, string> = {
      Accept: 'application/json',
      Origin: origin,
      'X-Requested-With': 'XMLHttpRequest',
      ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
    }

    const headers = {
      ...baseHeaders,
      ...customHeadersResult.headers,
    }

    const response = await $fetch<unknown>(polylineEndpoint, {
      method: 'GET',
      headers,
      credentials: 'include',
      query: {
        origin: originInput,
        destination: destinationInput,
      },
    })

    polylineResult.value = normalizePolylineResponse(response)
  } catch (error) {
    console.error('ポリライン情報の取得に失敗しました', error)

    if (error instanceof FetchError && error.response?.status === 401) {
      polylineError.value = 'セッションの有効期限が切れています。もう一度ログインしてください。'
      await router.replace('/login')
      return
    }

    polylineError.value = parseErrorMessage(error)
  } finally {
    polylineLoading.value = false
  }
}

const clearPolylineHeaders = () => {
  polylineForm.headersText = ''
  invalidHeaderLines.value = []
}

const formatCoordinate = (coordinate: PolylineCoordinate | null) => {
  if (!coordinate) {
    return '座標情報なし'
  }

  const { latitude, longitude } = coordinate
  const formatValue = (value: number | null) =>
    typeof value === 'number' && Number.isFinite(value) ? value.toFixed(5) : '—'

  return `${formatValue(latitude)}, ${formatValue(longitude)}`
}

const formatRating = (rating?: number) => {
  if (rating === undefined || Number.isNaN(rating)) {
    return '—'
  }

  return rating.toFixed(1)
}

const formatReviewCount = (count?: number) => {
  if (count === undefined || Number.isNaN(count)) {
    return ''
  }

  return `${Math.trunc(count)} 件のレビュー`
}

const formatRestaurantSubtitle = (restaurant: PolylineSampleRestaurant) => {
  const parts: string[] = []

  if (restaurant.vicinity) {
    parts.push(restaurant.vicinity)
  }

  const ratingText = formatRating(restaurant.rating)
  if (restaurant.rating !== undefined && ratingText !== '—') {
    parts.push(`評価 ${ratingText}`)
  }

  if (restaurant.reviewCount !== undefined) {
    const review = formatReviewCount(restaurant.reviewCount)
    if (review) {
      parts.push(review)
    }
  }

  return parts.join(' ・ ') || '詳細情報はありません'
}

const logout = async () => {
  if (isLoggingOut.value) {
    return
  }

  isLoggingOut.value = true
  logoutError.value = ''

  try {
    await ensureCsrfCookie()

    const xsrfToken = readXsrfToken()
    if (!xsrfToken) {
      throw new Error('CSRFトークンの取得に失敗しました。')
    }

    await $fetch(logoutEndpoint, {
      method: 'POST',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        Origin: getOriginHeader(),
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
    await loadRoutes()
  }
})
</script>
