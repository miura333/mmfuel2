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
htdigest -c .htdigest mmfuel2 YOUR_USERNAME
npm install
npm run build
cp .env dist/.env
```

`.env` に既存DBの接続情報を設定します。`.htaccess` の `AuthUserFile` はホスティング環境上の `.htdigest` の絶対パスへ変更します。本番のDocumentRootは `mmfuel2/dist` にしてください。ビルド時にAPI、`.htaccess`、`.htdigest` も `dist` へコピーされます。`.env` はビルドのたびに手動で `dist/.env` へコピーしてください。

ApacheでPHPスクリプト後方のPATH_INFO（例: `api/index.php/dashboard`）を利用できる必要があります。Digest認証はDocumentRoot全体（HTML、静的資産、Service Worker、API）に設定してください。

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
├── .htdigest
├── apple-touch-icon.png
├── index.html
├── manifest.webmanifest
├── sw.js
├── api/
│   ├── index.php
│   └── src/
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

`.env`、`.htaccess`、`.htdigest` はドットで始まる隠しファイルです。FTPクライアントで隠しファイルを表示し、この3ファイルも転送されていることを確認してください。

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

### 4. 公開先とDigest認証

公開URLに対応するホスティング側ディレクトリへ、`dist` の中身を配置します。`.htaccess` には次の形式のDigest認証設定が含まれています。

```apache
AuthType Digest
AuthName "mmfuel2"
AuthUserFile /absolute/path/to/mmfuel2/.htdigest
Require valid-user
```

`.htdigest` には平文パスワードではなくダイジェスト値が保存されています。HTMLだけでなく、次も同じDigest認証の範囲に含まれます。

- `api/`
- `assets/`
- `icons/`
- `manifest.webmanifest`
- `sw.js`

Safari/iOSが「ホーム画面に追加」のアイコンを認証情報なしで取得できるよう、`apple-touch-icon.png` だけは `.htaccess` でDigest認証の対象外にしています。このファイルはアプリのロゴ画像のみで、DBデータやAPIは公開されません。

`.htaccess` は `.env` などの隠しファイルへのHTTPアクセスを拒否します。アップロード後、ブラウザーから `https://公開URL/.env` を開き、内容が表示されず403または404になることを必ず確認してください。

### 5. アップロード後の確認

次の順番で確認します。

1. HTTPSの公開URLを開くとDigest認証が表示される
2. 認証後、車名と燃費が表示される
3. 車両一覧と履歴を表示できる
4. テスト用の給油記録を追加できる
5. ブラウザーの開発者ツールでAPIが200または201を返している
6. `manifest.webmanifest` と `sw.js` が404になっていない
7. スマートフォンでホーム画面へ追加し、再起動後もDigest認証と画面表示が動作する

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

## Digest認証とPWA

Digest認証下でも、同一オリジンで読み込まれるPWAとAPI通信は動作します。ブラウザーが最初の401応答で認証ダイアログを出し、その後の同一オリジン通信（Service Worker取得を含む）にも認証情報を用います。ただし次の制約があります。

- Service WorkerはHTTPS（localhostを除く）でのみ動作します。
- ホーム画面から起動した際、ブラウザーやOSの認証情報保持状態によっては再度Digest認証を求められます。対象端末・ブラウザーで実機確認が必要です。
- Digest認証には明確なログアウト機構がありません。
- 一度認証後にService Workerへキャッシュされた画面資産はオフライン表示できます。端末を共有する場合、Digest認証だけではローカルキャッシュを保護できません。
- このService WorkerはAPI応答をキャッシュしません。DBデータの表示・追加にはオンライン接続と認証が必要です。

1人専用かつ個人端末であれば現実的な構成ですが、配備後にChrome/AndroidとSafari/iOSの「インストール、ホーム画面起動、認証期限後の再起動」を確認してください。

## セキュリティ上の補足

- 更新APIは `Content-Type: application/json` と同一Originを要求します。
- SQLはすべてPDOプリペアドステートメントを使用します。
- 指定した車両が `user_id = 1` の所有物かAPI側で検証します。
- Digest認証も必ずHTTPS上で使用してください。
