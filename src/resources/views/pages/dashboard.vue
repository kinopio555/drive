<template>
  <v-main>
    <v-container class="py-8">
      <v-row justify="center">
        <v-col cols="12" md="8" lg="6">
          <v-card elevation="4" class="pa-6 text-center">
            <v-icon size="56" color="primary" class="mb-4">mdi-view-dashboard</v-icon>
            <h1 class="text-h4 font-weight-bold mb-2">ダッシュボード</h1>
            <p class="text-body-1 mb-6">
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
            <v-divider class="my-4" />
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

const userName = ref<string>('')
const loading = ref(true)
const isLoggingOut = ref(false)
const logoutError = ref('')

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
  return tokenCookie.value ? decodeURIComponent(tokenCookie.value) : ''
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

const loadUser = async () => {
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
      return
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
  } catch (error) {
    console.error('ユーザー情報の取得に失敗しました', error)
  } finally {
    loading.value = false
  }
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

onMounted(() => {
  loadUser()
})
</script>
