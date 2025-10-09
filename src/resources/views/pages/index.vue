<template>
  <v-main>
    <v-container class="fill-height">
      <v-row class="fill-height" align="center" justify="center">
        <v-col cols="12" md="8" lg="6">
          <v-sheet class="pa-8 text-center" elevation="6" rounded>
            <v-icon size="64" color="primary" class="mb-4">mdi-account-plus</v-icon>
            <h1 class="text-h4 font-weight-bold mb-4">ようこそ</h1>
            <p class="text-body-1 mb-6">
              Vuetifyを利用したシンプルなサインアップ画面のデモです。
              下のボタンから新規登録ページへ移動できます。
            </p>
            <div class="d-flex flex-column flex-sm-row ga-4 justify-center">
              <v-btn
                color="primary"
                size="large"
                to="/register"
                append-icon="mdi-arrow-right"
              >
                新規登録へ
              </v-btn>

              <v-btn
                color="secondary"
                size="large"
                variant="outlined"
                to="/login"
                append-icon="mdi-login"
              >
                ログイン
              </v-btn>
            </div>
          </v-sheet>
        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>
