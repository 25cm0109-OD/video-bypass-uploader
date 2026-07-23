# Discord Video Uploader

Discordへ直接送信しにくい大容量動画をアップロードし、共有用URLを発行するWebアプリです。シンプルな操作で、最大500MBの動画を共有できます。

## 主な機能

- ドラッグ＆ドロップによる動画アップロード
- MP4 / WebM / OGG / MOV形式に対応
- アップロード進捗の表示と共有URLのコピー
- MOV動画のMP4自動変換（FFmpeg導入時）
- 管理画面でのファイル一覧表示・削除

## 使用技術

- PHP
- JavaScript
- HTML / CSS
- Apache
- FFmpeg（任意）

## ローカルでの起動

PHP 8以降を用意し、次のコマンドを実行します。

```bash
php -d upload_max_filesize=500M \
    -d post_max_size=520M \
    -S localhost:8000 -t public
```

ブラウザで `http://localhost:8000` を開きます。

> [!NOTE]
> 管理画面（`/admin/`）には認証機能がないため、公開環境ではアクセス制限を設定してください。
