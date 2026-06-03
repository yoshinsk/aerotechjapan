# エアロテックジャパン Web サイト（aero-tech.co.jp）

[www.aero-tech.co.jp](https://www.aero-tech.co.jp) として公開されている
コーポレート／商品紹介サイトのソースです。公開ディレクトリは `httpdocs/` です。

このドキュメントは、**(1) 現状のコードレビュー結果**、**(2) 今後の方針（レスポンシブ化・
商品情報の編集容易化）**、**(3) 方針を検証するための試作版（prototype）** をまとめたものです。

---

## 1. 現状の構成（コードレビュー結果）

### 概要

- **種別**: 2000年代中盤に制作された、**フレームセット型の静的 HTML サイト**。
- **文字コード**: ほぼ全ページ `Shift_JIS`（一部 `index.php` は `ISO-2022-JP`）。
- **レイアウト**: `<table>` による段組み＋固定幅（`width="900"/"690"/"450"`）。
  レスポンシブ要素は無く、スマートフォンでは横スクロールが発生する。
- **ナビゲーション**: 画像（GIF）＋ Dreamweaver の `MM_swapImage` による
  ロールオーバー JavaScript。
- **CSS**: `httpdocs/style.css` は実質 1 行（`H1` のみ）。デザインは画像とテーブルに依存。
- **規模（概算）**: HTML 約 2,300 / JPG 約 6,600 / PDF 約 470 / GIF 約 460。

### 画面構成（フレーム）

`httpdocs/index.html` が frameset で以下を読み込む。

| フレーム | ファイル | 役割 |
|----------|----------|------|
| top  | `f-top.html`  | ロゴ＋上部メニュー（EVENT / MAGAZINE / ORDER / SITE MAP / LINK / ABOUT） |
| left | `f-left.html` | 左サイドメニュー（AERO PARTS の各ブランド、DASH/SIDE TABLE 等） |
| main | `f-main.html` | トップ本文（ランダム画像・お知らせ iframe・新着商品） |

### 商品データの持ち方（編集の要点）

商品は **すべて手書きの HTML ファイル**として管理されている。

| 種類 | 場所 | 内容 |
|------|------|------|
| カテゴリ一覧 | `aero-parts/aero-parts-01〜08.html` | ブランド別（乱人 / 乱人 Black Edition / DIRect / AVANT / RANDO Style / RANDO SPORTS / 乱人流 SPORTS / Rando Ryu LUX）の車種リンク集 |
| 商品詳細 | `garage-file/*.html`（**約 128 ファイル**） | 写真リスト＋ SPEC 表を手書き |
| 拡大画像 | `garage-img/<商品名>/`（**約 153 フォルダ**） | 画像ごとに HTML ラッパーを生成 |
| ダッシュ／サイドテーブル | `dash-boad-table/`, `parts*.html` | 適合表 |
| お知らせ | `news-window.html` | main に iframe 表示 |

### 動的要素・その他

- `cgi-bin/fmail/` … Perl 製のメール送信フォーム（`fmail.cgi`）。
- `acme.log` … `acme.sh` による Let's Encrypt 証明書の自動更新ログ（Plesk 系の共有ホスト想定）。
- 外部アクセス解析タグ（research-artisan / jnetstation）。

### 主な課題

1. **モバイル非対応**: 固定幅テーブルのため、スマホで非常に見づらい。
2. **編集コストが高い**: 商品 1 件の追加に「詳細 HTML 手書き＋画像フォルダ作成＋
   カテゴリページへのリンク追記」が必要。
3. **文字化けリスク**: Shift_JIS のため、編集環境次第で容易に文字化けする。
4. **重複コード**: ヘッダ・メニュー・SPEC 表などが全ページにコピーされている。

---

## 2. 今後の方針（ゴール）

**デザインの雰囲気（黒背景・白文字・シアンのリンク）を維持しつつ**、以下を実現する。

- **レスポンシブ Web デザイン**: **Bootstrap 5** を採用（CDN 読み込み、ビルド不要）。
  frameset を廃止し、1 枚のレスポンシブ HTML に統合する。
- **商品情報の編集容易化**: **PHP** によるサーバーサイドのデータ駆動表示とし、
  **ページ単位（商品単位）で編集**できる管理フォームを用意する。
- **データストア**: **MariaDB**（`utf8mb4`）。まずは JSON ファイルでも動作し、
  設定 1 つで MariaDB に切り替え可能な構成とする。
- **文字コード**: **UTF-8 へ移行**（編集時の文字化けを根絶）。
- **運用前提**: 共有ホスト上で**ファイルを直接編集**する運用（Node 等のビルド工程は不要）。

---

## 3. 試作版（prototype）

方針を検証するため、**1 商品分**（`GUN125 HILUX GR SPORT`）の
レスポンシブ＋データ駆動ページを `httpdocs/prototype/` に用意した。
**既存サイトには一切手を加えていない**（独立して動作）。

### ディレクトリ

```
httpdocs/prototype/
├── config.php            設定（DB/JSON 切替・DB接続情報・編集パスワード）
├── index.php             商品一覧（カード表示）
├── product.php           商品詳細（レスポンシブ・データ駆動）
├── schema.sql            MariaDB スキーマ＋シードデータ
├── lib/
│   └── repository.php    商品データのリポジトリ（MariaDB/JSON を吸収）
├── templates/
│   └── layout.php        共通レイアウト（ヘッダ／サイドメニュー／フッタ）
├── admin/
│   └── edit.php          簡易編集フォーム（ページ単位の編集）
├── data/
│   └── products.json     シードデータ（DB不要で動かすため）
└── assets/
    └── site.css          配色などのデザイン調整
```

### 動かし方

PHP が動く環境（PHP 7.4 以上）で `httpdocs/` を配信し、ブラウザで開く。

```
# 手元で素早く確認する例（httpdocs/ をドキュメントルートにする）
php -S localhost:8000 -t httpdocs
```

- 商品一覧 : `http://localhost:8000/prototype/index.php`
- 商品詳細 : `http://localhost:8000/prototype/product.php?slug=GUN125_HILUX_GR_SPORT`
- 編集画面 : `http://localhost:8000/prototype/admin/edit.php?slug=GUN125_HILUX_GR_SPORT`
  （編集パスワードの初期値は `config.php` の `ADMIN_PASSWORD` = `aerotech-demo`）

> 注: 商品画像は既存の `httpdocs/garage-img/GUN125_HILUX_GR_SPORT/` を参照する。

### データソースの切り替え

`httpdocs/prototype/config.php` の 1 行で切り替わる。

- `USE_DB = false` … `data/products.json` を読み書き（**DB 不要**ですぐ確認できる）。
- `USE_DB = true`  … MariaDB に接続。先に `schema.sql` を流し、`DB_*` を環境に合わせる。

```sql
mysql -u root -p < httpdocs/prototype/schema.sql
```

---

## 4. 本番への移行ロードマップ（案）

1. **試作の確認**（本コミット）: 1 商品でレスポンシブ＋編集を検証。
2. **共通テンプレート化**: `layout.php` にヘッダ／メニュー／フッタを集約。
   旧 frameset を廃止。
3. **データ移行**: `garage-file/*.html` の SPEC・画像情報をスクリプトで抽出し、
   UTF-8 で MariaDB（または JSON）へ取り込み。
4. **全商品をデータ駆動化**: カテゴリ一覧・詳細・拡大画像を `product.php` 系へ統合。
5. **管理画面の拡充**: 認証強化（セッション＋ハッシュ化、HTTPS 必須）、
   画像アップロード、商品の追加・削除・並び替え。
6. **UTF-8 完全移行**: 残存する Shift_JIS ページ・メールフォームの文字コードを統一。
7. **リダイレクト整備**: 旧 URL（`garage-file/xxx.html`）から新 URL への 301 リダイレクト。

---

## 5. 注意事項

- 試作版の編集フォームはパスワード 1 個のみの**簡易認証**。本番では必ず
  セッション認証・パスワードのハッシュ化・HTTPS 必須に置き換えること。
- `cgi-bin/`・`acme.log`・各種解析タグは現行運用に関わるため、移行時に扱いを要確認。
- コミット時、`Thumbs.db` / `.DS_Store` / `_notes/`（Dreamweaver）等の不要ファイルは
  整理対象。
