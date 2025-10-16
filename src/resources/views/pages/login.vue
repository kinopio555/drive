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
  const fallback = 'ログインに失敗しました。時間をおいて再度お試しください。'

  const toTrimmedString = (value: unknown): string | undefined => {
    if (typeof value === 'string') {
      const trimmed = value.trim()
      if (trimmed.length > 0) {
        return trimmed
      }
    }
    return undefined
  }

  const visited = new WeakSet<object>()

  const fromObject = (payload: unknown): string | undefined => {
    if (!payload || typeof payload !== 'object') {
      return undefined
    }

    const objectPayload = payload as object
    if (visited.has(objectPayload)) {
      return undefined
    }
    visited.add(objectPayload)

    const record = payload as Record<string, unknown>

    const errors = record.errors
    if (errors && typeof errors === 'object') {
      for (const value of Object.values(errors as Record<string, unknown>)) {
        if (Array.isArray(value)) {
          for (const entry of value) {
            const message = toTrimmedString(entry)
            if (message) {
              return message
            }
          }
        } else {
          const message = toTrimmedString(value)
          if (message) {
            return message
          }
        }
      }
    }

    const directErrorField = toTrimmedString(record.error)
    if (directErrorField) {
      return directErrorField
    }

    const nestedSources = [
      record.data,
      (record.response && typeof record.response === 'object' ? (record.response as { _data?: unknown })._data : undefined),
      record.error,
    ]

    for (const source of nestedSources) {
      const nestedMessage = fromObject(source)
      if (nestedMessage) {
        return nestedMessage
      }
    }

    const directMessage = toTrimmedString(record.message)
    if (directMessage) {
      return directMessage
    }

    const statusMessage = toTrimmedString(record.statusMessage ?? record.statusText)
    if (statusMessage) {
      return statusMessage
    }

    return undefined
  }

  const directString = toTrimmedString(error)
  if (directString) {
    return directString
  }

  const objectMessage = fromObject(error)
  if (objectMessage) {
    return objectMessage
  }

  if (error instanceof Error) {
    return toTrimmedString(error.message) ?? fallback
  }

  return fallback
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
