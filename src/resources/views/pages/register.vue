<template>
  <v-main>
    <v-container class="fill-height">
      <v-row class="d-flex align-center justify-center fill-height">
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card elevation="6">
            <v-toolbar color="primary" dark flat>
              <v-toolbar-title>新規登録</v-toolbar-title>
            </v-toolbar>

            <v-card-text>
              <v-form ref="formRef" v-model="isValid" lazy-validation @submit.prevent="submit">
                <v-text-field
                  v-model="form.name"
                  :rules="[rules.required]"
                  label="氏名"
                  prepend-icon="mdi-account"
                  autocomplete="name"
                  variant="outlined"
                />

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
                  :rules="[rules.required, rules.passwordLength]"
                  label="パスワード"
                  prepend-icon="mdi-lock"
                  autocomplete="new-password"
                  variant="outlined"
                  @click:append-inner="togglePassword"
                />

                <v-text-field
                  v-model="form.passwordConfirmation"
                  :type="showPassword ? 'text' : 'password'"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  :rules="[rules.required, rules.passwordMatch]"
                  label="パスワード（確認用）"
                  prepend-icon="mdi-lock-check"
                  autocomplete="new-password"
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
                  color="primary"
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
                  {{ isSubmitting ? '登録処理中...' : '登録する' }}
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <v-snackbar v-model="snackbar" color="primary" timeout="2500">
      登録リクエストを送信しました。
    </v-snackbar>
  </v-main>
</template>

<script setup lang="ts">
import { $fetch, FetchError } from 'ofetch'
import { useCookie } from '#app'
import { reactive, ref } from 'vue'

const apiBaseUrl = 'http://localhost:8080'
const csrfEndpoint = `${apiBaseUrl}/sanctum/csrf-cookie`
const apiEndpoint = `${apiBaseUrl}/register`
const logoutEndpoint = `${apiBaseUrl}/logout`
const isLoginEndpoint = `${apiBaseUrl}/is-login`

const form = reactive({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
})

const showPassword = ref(false)
const isValid = ref(false)
const isSubmitting = ref(false)
const snackbar = ref(false)
const errorMessage = ref('')

const formRef = ref()

const rules = {
  required: (value: string | boolean) => !!value || '必須項目です',
  email: (value: string) => /.+@.+\..+/.test(value) || '正しいメールアドレスを入力してください',
  passwordLength: (value: string) => value.length >= 8 || '8文字以上で入力してください',
  passwordMatch: () => form.password === form.passwordConfirmation || 'パスワードが一致しません',
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const readXsrfToken = () => {
  const tokenCookie = useCookie<string | null>('XSRF-TOKEN')
  return tokenCookie.value ? decodeURIComponent(tokenCookie.value) : ''
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

const ensureGuestSession = async (xsrfToken: string) => {
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
      await $fetch(logoutEndpoint, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-XSRF-TOKEN': xsrfToken,
        },
      })

      return true
    }
  } catch (error) {
    console.error('ログアウト状態の確認に失敗しました', error)
  }

  return false
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

  return '登録に失敗しました。時間をおいて再度お試しください。'
}

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

    let xsrfToken = readXsrfToken()
    if (!xsrfToken) {
      throw new Error('CSRFトークンの取得に失敗しました。')
    }

    const loggedOut = await ensureGuestSession(xsrfToken)

    if (loggedOut) {
      await $fetch(csrfEndpoint, {
        method: 'GET',
        credentials: 'include',
      })

      xsrfToken = readXsrfToken()

      if (!xsrfToken) {
        throw new Error('CSRFトークンの再取得に失敗しました。')
      }
    }

    await $fetch(apiEndpoint, {
      method: 'POST',
      credentials: 'include',
      body: {
        name: form.name,
        email: form.email,
        password: form.password,
        password_confirmation: form.passwordConfirmation,
      },
      headers: {
        Accept: 'application/json',
        'X-XSRF-TOKEN': xsrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    snackbar.value = true
    formRef.value?.resetValidation()
    Object.assign(form, {
      name: '',
      email: '',
      password: '',
      passwordConfirmation: '',
    })
  } catch (error: unknown) {
    if (error instanceof FetchError && error.response?.status === 302) {
      errorMessage.value = '登録処理がリダイレクトされました。ログアウト後に再度お試しください。'
      return
    }

    errorMessage.value = parseErrorMessage(error)
  } finally {
    isSubmitting.value = false
  }
}
</script>
