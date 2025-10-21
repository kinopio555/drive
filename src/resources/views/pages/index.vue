<template>
  <v-main>
    <v-container class="fill-height">
      <v-row class="fill-height" align="center" justify="center">
        <v-col cols="12" md="8" lg="7">
          <v-sheet class="pa-8 text-center" elevation="6" rounded>
            <v-icon size="64" color="primary" class="mb-4">mdi-account-plus</v-icon>
            <h1 class="text-h4 font-weight-bold mb-4">ようこそ</h1>
            <p class="text-body-1 mb-6">
              ダッシュボードからドライブや散歩の経路周辺にある飲食店を検索できます！<br>
              お気に入りの経路周辺の飲食店は保存しておきましょう👍
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
