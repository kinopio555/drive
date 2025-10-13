# Docker Hubの公式MySQLイメージをベースにする
FROM mysql:8.0

# --- 日本語ロケール設定 ---
# タイムゾーンとロケール用の環境変数を設定
ENV TZ=Asia/Tokyo \
    LANG=ja_JP.UTF-8 \
    LC_ALL=ja_JP.UTF-8

# パッケージマネージャーを使い、日本語言語パックをインストール
# これにより、コンテナのOSが日本語を「理解」できるようになる
# (mysql:8.0はOracle Linuxベースのため microdnf を使用)
RUN microdnf install -y glibc-langpack-ja && \
    microdnf clean all

# --- MySQL設定 ---
# ホストにあるmy.cnfをコンテナ内の設定ディレクトリにコピーする
COPY ./my.cnf /etc/mysql/conf.d/my.cnf


