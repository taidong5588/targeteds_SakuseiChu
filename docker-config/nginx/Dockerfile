# 安定版 Nginx イメージをベースに使用
FROM nginx:stable-alpine

LABEL maintainer="HubX"

# 作業ディレクトリ（任意）
WORKDIR /etc/nginx

# Nginx 設定ファイルのコピー
COPY default.conf /etc/nginx/conf.d/default.conf

# ログディレクトリ作成と権限設定（イメージ内）
RUN mkdir -p /var/log/nginx \
    && touch /var/log/nginx/access.log /var/log/nginx/error.log \
    && chown -R nginx:nginx /var/log/nginx \
    && chmod 755 /var/log/nginx
    
# ポート公開
EXPOSE 80