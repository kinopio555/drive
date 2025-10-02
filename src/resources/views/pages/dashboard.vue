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

type RouteWithRestaurants = {
  id: string
  origin: string
  destination: string
  restaurantNames: string[]
}

const userName = ref<string>('')
const loading = ref(true)
const isLoggingOut = ref(false)
const logoutError = ref('')
const routes = ref<RouteWithRestaurants[]>([])
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

