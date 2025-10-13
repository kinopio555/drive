<template>
  <v-main>
    <v-container class="fill-height">
      <v-row class="d-flex align-center justify-center fill-height">
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card elevation="6">
            <v-toolbar color="secondary" dark flat>
              <v-toolbar-title>ログイン</v-toolbar-title>
            </v-toolbar>

            <v-card-text>
              <v-form ref="formRef" v-model="isValid" lazy-validation @submit.prevent="submit">
                <v-text-field
                  v-model="form.email"
                  :rules="[rules.required, rules.email]"
                  label="メールアドレス"
                  prepend-icon="mdi-email"
                  autocomplete="email"
                  variant="outlined"
                />

                <v-text-field
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  :rules="[rules.required]"
                  label="パスワード"
                  prepend-icon="mdi-lock"
                  autocomplete="current-password"
                  variant="outlined"
                  @click:append-inner="togglePassword"
                />

                <v-alert
                  v-if="errorMessage"
                  type="error"
                  variant="tonal"
                  class="mt-2"
                  density="comfortable"
                >
                  {{ errorMessage }}
                </v-alert>

                <v-btn
                  block
                  class="mt-4"
                  color="secondary"
                  size="large"
                  type="submit"
                  :disabled="!isValid || isSubmitting"
                >
                  <template #prepend>
                    <v-progress-circular
                      v-if="isSubmitting"
                      indeterminate
                      size="16"
                      width="2"
                      color="white"
                    />
                  </template>
                  {{ isSubmitting ? 'ログイン処理中...' : 'ログイン' }}
                </v-btn>

                <v-btn
                  class="mt-3"
                  size="large"
                  color="primary"
                  variant="text"
                  to="/register"
                >
                  新規登録はこちら
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<script setup lang="ts">
import { $fetch, FetchError } from 'ofetch'
import { useCookie } from '#app'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from '#imports'

const router = useRouter()

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
const csrfEndpoint = `${apiBaseUrl}/sanctum/csrf-cookie`
const loginEndpoint = `${apiBaseUrl}/login`
const isLoginEndpoint = `${apiBaseUrl}/is-login`

const form = reactive({
  email: '',
  password: '',
})

const showPassword = ref(false)
const isValid = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')

const formRef = ref()

const rules = {
  required: (value: string | boolean) => !!value || '必須項目です',
  email: (value: string) => /.+@.+\..+/.test(value) || '正しいメールアドレスを入力してください',
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
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

  return 'ログインに失敗しました。時間をおいて再度お試しください。'
}

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

const redirectIfAuthenticated = async () => {
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

    if (coerceToBoolean(loginStatus)) {
      await router.replace('/dashboard')
    }
  } catch (error) {
    console.error('ログイン状態の確認に失敗しました', error)
  }
}

onMounted(() => {
  redirectIfAuthenticated()
})

const submit = async () => {
  const validationResult = await formRef.value?.validate()
  if (!validationResult || (typeof validationResult === 'object' && 'valid' in validationResult && !validationResult.valid)) {
    return
  }

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    await $fetch(csrfEndpoint, {
      method: 'GET',
      credentials: 'include',
    })

    const xsrfToken = readXsrfToken()
    if (!xsrfToken) {
      throw new Error('CSRFトークンの取得に失敗しました。')
    }

    await $fetch(loginEndpoint, {
      method: 'POST',
      credentials: 'include',
      body: {
        email: form.email,
        password: form.password,
      },
      headers: {
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    await router.push('/dashboard')
  } catch (error: unknown) {
    if (error instanceof FetchError && error.response?.status === 302) {
      await router.push('/dashboard')
      return
    }

    errorMessage.value = parseErrorMessage(error)
  } finally {
    isSubmitting.value = false
  }
}
</script>
