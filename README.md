# docker_languages
tree -L2
.
├── README.md
├── docker-compose.yml
├── .env
├── docker-config/
│   ├── db/
│   │   ├── conf/my.cnf              # MariaDB の設定ファイル
│   │   └── sql/install.sql          # 初期データ投入用 SQL
│   ├── logs/                        # nginx や PHP のログ出力先
│   ├── nginx/
│   │   ├── Dockerfile               # Nginx のビルド用
│   │   └── default.conf             # Nginx の仮想ホスト設定
│   └── php/
│       ├── Dockerfile               # PHP + Laravel 実行環境のビルド用
│       └── php.ini                  # PHP 設定ファイル（開発向け）
└── src/
    └── index.php                    # Laravel またはシンプルなPHPコード


docker compose --env-file .env up -d --build
docker compose up -d --build

docker compose exec php bash
composer create-project laravel/laravel .
composer create-project laravel/laravel . "11.*"


<!-- Jetstreamパッケージのインストール -->
composer require laravel/jetstream
<!-- Livewireを使用する場合 -->
php artisan jetstream:install livewire

<!-- Inertia.jsを使用する場合 -->
php artisan jetstream:install inertia --teams
npm install
npm run build

npm install vue@^3 vue-router@^4
npm install @inertiajs/vue3
npm install socket.io-client # チャット用
npm install fullcalendar # カレンダー用

<!-- Filament管理画面のインストール -->
# Filamentパッケージのインストール
composer require filament/filament:"^3.0"
# Filamentのインストール
php artisan filament:install --panels
# 管理者ユーザーの作成
php artisan make:filament-user


# Laravelの.envファイル編集（DB設定）

DB_CONNECTION=mysql
DB_HOST=DB
DB_PORT=3306
DB_DATABASE=lang_db
DB_USERNAME=lang
DB_PASSWORD=lang

# 権限変更とマイグレーション
| コマンド           | 意味                                  | セキュリティ    |
| -------------- | ----------------------------------- | --------- |
| `chmod -R 775` | 所有者とグループに読み・書き・実行を許可。その他は読み・実行のみ許可。 | 安全寄り 🔒   |
| `chmod -R 777` | **すべてのユーザーに** 読み・書き・実行を許可。          | 危険（開発用）⚠️ |

docker compose exec php chown -R www-data:www-data storage bootstrap/cache
docker compose exec php chmod -R 777 storage bootstrap/cache


composer install
php artisan key:generate
php artisan migrate
npm install
npm run build

php artisan storage:link

キャッシュをクリア
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

オートロードの更新:
composer dump-autoload

# 再ビルドと再起動
docker compose down
docker compose build --no-cache
docker compose up -d

# Laravelのstorageとbootstrap/cacheディレクトリに書き込み権限を付与
docker-compose exec php chmod -R 775 /var/www/storage
docker-compose exec php chmod -R 775 /var/www/bootstrap/cache
docker-compose exec php chmod -R 775 /var/www/agent/storage
docker-compose exec php chmod -R 775 /var/www/agent/bootstrap/cache
docker-compose exec php chmod -R 775 /var/www/languages/storage
docker-compose exec php chmod -R 775 /var/www/languages/bootstrap/cache

# 動作確認
open http://localhost
open http://localhost:8081  # phpMyAdmin

######
# https://laravel-lang.com/packages-lang.html
# https://github.com/Laravel-Lang/lang/blob/main/locales/ja/json.json
######

# Docker 内で実行（/var/www 配下）
docker compose exec php bash

# composer 再インストール
composer require laravel-lang/lang laravel-lang/publisher

# Laravel-Lang 本体
composer require laravel-lang/lang

# 翻訳ファイルパブリッシュ用
composer require laravel-lang/publisher

# 翻訳ファイルの追加（必要言語だけ指定）
php artisan lang:add ja en zh_CN ko

# 翻訳ファイルを lang ディレクトリに公開
php artisan lang:publish

###
# https://vue-i18n.intlify.dev/guide/installation
# https://zenn.dev/blancpanda/articles/jetstream-vue-i18n
###
npm install vue-i18n@11

# 🧹 0. 作業ディレクトリ初期化
rm -rf ./*
rm -rf ./.*

# Git を完全に削除
rm -rf .git

# ④ 再実行（ローカル）
php artisan migrate:fresh --seed

# Seeder 実行
php artisan db:seed

php artisan tinker
$user = App\Models\AdminUser::where('email', 'admin@gmail.com')->first();

# 🔒 本番では必須
# QUEUE_CONNECTION=redis（or database）
# Supervisor / systemd / Horizon で worker 常駐
# deploy 時に 必ず

php artisan queue:failed
# または
php artisan optimize:clear
php artisan queue:restart
php artisan queue:work
