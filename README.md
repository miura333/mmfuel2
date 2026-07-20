# mmfuel2

旧 `mmfuel` のデータベースをそのまま利用する、PHP 8 + Vue 3製の燃費管理PWAです。LaravelなどのPHPフレームワークやComposerパッケージは使用しません。

## 構成

- `api/`: PHP 8 / PDO製JSON API
- `src/`: Vue 3フロントエンド
- `public/`: PWAマニフェスト、Service Worker、アイコン
- `dist/`: `npm run build` で生成される配備物（Git管理外）

APIが扱う機能は、車両一覧、車両追加、ダッシュボード、履歴、給油記録追加です。すべてのDB操作で `user_id = 1` を使用します。DBのマイグレーションは行わず、既存の `cars` と `fuel_records` をそのまま使います。

## 必要環境

- PHP 8.0以上（PDO MySQL、mbstring）
- MySQL（既存mmfuel DB）
- Node.js 16.14以上（フロントエンドのビルド時のみ）
- 本番環境ではHTTPS（Service Workerの必須条件）

## セットアップ

```sh
cp .env.example .env
cp .htaccess.example .htaccess
npm install
npm run build
cp .env dist/.env
```

`.env` に既存DBの接続情報とCookie認証設定を記入します。ログイン用トークンとCookie署名用シークレットは、次のコマンドを2回実行して別々の値を生成してください。

```sh
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

```dotenv
APP_BASE_PATH=/mmfuel2
APP_LOGIN_TOKEN=1回目に生成した値
APP_COOKIE_SECRET=2回目に生成した値
```

`APP_BASE_PATH` は公開URLのパスです。ルート直下で公開する場合は `/` にします。本番のDocumentRootは `mmfuel2/dist` にしてください。ビルド時にAPIと `.htaccess` も `dist` へコピーされます。`.env` はビルドのたびに手動で `dist/.env` へコピーしてください。

ApacheでPHPスクリプト後方のPATH_INFO（例: `api/index.php/dashboard`）と `mod_rewrite` を利用できる必要があります。DBを扱うAPIはCookie認証で保護されます。

## ロリポップへFTPでアップロードする

アップロードするのは、プロジェクト全体ではなく、ビルド後の **`dist` ディレクトリの中身だけ** です。`dist` ディレクトリ自体をアップロードするのではなく、その中のファイルとディレクトリを公開先へ配置します。

### 1. アップロード前の準備

ローカルの `mmfuel2` ディレクトリで次を実行します。

```sh
npm install
npm run build
cp .env dist/.env
```

`npm run build` を実行すると `dist` は作り直されるため、**必ずビルド後に** `.env` をコピーします。`dist/.env` のDB設定がロリポップ上の既存mmfuel DBを指していることを確認してください。

### 2. FTPでアップロードするもの

FTPクライアントで、ロリポップ側のmmfuel2公開先ディレクトリを開き、ローカルの `mmfuel2/dist/` にある次の内容をすべてアップロードします。

```text
dist/
├── .env
├── .htaccess
├── apple-touch-icon.png
├── index.html
├── manifest.webmanifest
├── sw.js
├── api/
│   ├── auth.php
│   ├── index.php
│   └── src/
│       ├── Auth.php
│       ├── Config.php
│       ├── Database.php
│       ├── FuelRepository.php
│       └── Http.php
├── assets/
│   ├── index-xxxxxxxx.css
│   └── index-xxxxxxxx.js
└── icons/
    ├── icon-120.png
    ├── icon-180.png
    ├── icon-192.png
    ├── icon-512.png
    └── icon.svg
```

`assets` 内のファイル名にはビルドごとのハッシュが付くため、上記の `xxxxxxxx` と実際の名前は異なります。ファイルを個別に選ばず、`assets` と `icons` はディレクトリごとアップロードしてください。

`.env` と `.htaccess` はドットで始まる隠しファイルです。FTPクライアントで隠しファイルを表示し、この2ファイルも転送されていることを確認してください。旧Digest認証で使っていた `.htdigest` はサーバーから削除します。

### 3. アップロードしないもの

次の開発用ファイルはロリポップへアップロードしません。

- `node_modules/`
- `src/`
- `public/`
- プロジェクト直下の `api/`
- `package.json`、`package-lock.json`、`vite.config.js`
- `.gitignore`、`README.md`
- `.DS_Store`
- 旧アプリの `mmfuel/` 一式

APIはすでに `dist/api` へコピーされているため、プロジェクト直下の `api` を別途アップロードする必要はありません。Node.jsもロリポップ上では不要です。

### 4. 公開先とCookie認証

公開URLに対応するホスティング側ディレクトリへ、`dist` の中身を配置します。`.htaccess` は `/login` をCookie発行用PHPへ転送します。

```apache
RewriteEngine On
RewriteRule ^login/?$ api/auth.php [L,QSA]
```

初回だけ、Safariで次の形式のURLを開きます。

```text
https://example.com/mmfuel2/login?token=APP_LOGIN_TOKENの値
```

現在の設定から実際の初回ログインURLを表示する場合は、ローカルの `mmfuel2` ディレクトリで次を実行します。出力されたURLは第三者へ共有しないでください。

```sh
awk -F= '$1 == "APP_LOGIN_TOKEN" { print "https://miutex.site/mmfuel2/login?token=" $2 }' .env
```

トークンが一致すると、サーバーは `app_auth` Cookieを発行し、トークンを含まない通常URLへリダイレクトします。その後に「ホーム画面に追加」を行います。Cookieは1年間有効で、`Secure`、`HttpOnly`、`SameSite=Lax`、公開パスに限定した `Path` が設定されます。

静的なHTML、JavaScript、CSS、PWAアイコンにはDBデータや認証情報を含めないため公開されます。`cars` と `fuel_records` を読み書きするAPIは、有効なCookieがなければ401を返します。

`.htaccess` は `.env` などの隠しファイルへのHTTPアクセスを拒否します。アップロード後、ブラウザーから `https://公開URL/.env` を開き、内容が表示されず403または404になることを必ず確認してください。

### 5. アップロード後の確認

次の順番で確認します。

1. HTTPSの通常URLを開くとAPIが401になり、DBデータが表示されない
2. 初回ログインURLを開くと通常URLへリダイレクトされる
3. `app_auth` Cookieに `Secure`、`HttpOnly`、`SameSite=Lax` が付いている
4. 認証後、車名と燃費が表示される
5. 車両一覧と履歴を表示できる
6. テスト用の給油記録を追加できる
7. ブラウザーの開発者ツールでAPIが200または201を返している
8. `manifest.webmanifest` と `sw.js` が404になっていない
9. ホーム画面へ追加し、PWAを再起動してもデータが表示される

### 6. 更新時の注意

更新時は再度 `npm run build` を実行し、`dist` の中身をアップロードします。古いハッシュ付きファイルが残らないよう、公開先の `assets` ディレクトリは新しい `dist/assets` と入れ替えてください。DBデータや既存テーブルをアップロード・更新する作業はありません。

## ローカル開発

ターミナルを2つ使います。

```sh
cp .env.example .env
php -S 127.0.0.1:8080
```

```sh
npm run dev
```

Viteは `/api` をPHP開発サーバーへプロキシします。ブラウザーで表示されたViteのURLを開きます。

## Cookie認証とPWA

Safariで初回ログインURLを開いてCookieを発行した後にホーム画面へ追加します。PWAは同一オリジンのAPIへCookieを送信するため、Digest認証ダイアログに依存しません。

- Service WorkerはHTTPS（localhostを除く）でのみ動作します。
- Cookieを削除した場合、1年が経過した場合、または `APP_COOKIE_SECRET` を変更した場合は、初回ログインURLへ再度アクセスします。
- 初回ログインURLのトークンはブラウザー履歴やサーバーログに残る可能性があります。第三者へ送らず、漏えいが疑われる場合は `APP_LOGIN_TOKEN` と `APP_COOKIE_SECRET` の両方を変更してください。
- 一度認証後にService Workerへキャッシュされた画面資産はオフライン表示できますが、API応答はキャッシュしません。DBデータの表示・追加にはオンライン接続と認証が必要です。

この方式は1人専用・個人端末向けの簡易認証です。複数ユーザー、共有端末、権限管理が必要な用途には使用しません。

## セキュリティ上の補足

- 更新APIは `Content-Type: application/json` と同一Originを要求します。
- SQLはすべてPDOプリペアドステートメントを使用します。
- 指定した車両が `user_id = 1` の所有物かAPI側で検証します。
- CookieにはログインURLのトークンを直接保存せず、別のシークレットからHMACで生成した値を保存します。
- Cookie認証と初回ログインURLは必ずHTTPS上で使用してください。
