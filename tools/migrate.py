#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
旧サイト（Shift_JIS の静的HTML）から商品情報を抽出し、
UTF-8 の products.json と MariaDB 用 products.sql を生成する一回限りの移行ツール。

抽出元:
  httpdocs/garage-file/*.html       … 商品詳細（商品名・年式・画像・SPEC）
  httpdocs/aero-parts/aero-parts-0N.html … 商品→カテゴリの対応

出力:
  httpdocs/prototype/data/products.json
  httpdocs/prototype/data/products.sql

設計メモ:
  - 画像はファイル名規則が世代ごとにバラバラなため、HTMLが実際に参照している
    ../garage-img/*.jpg を「文書順」で抜き出す（コメントアウト分は除外）。
  - SPEC表は bordercolor="#666666" のテーブルを対象に best-effort で label|value 化。
  - 完全自動化は困難なので、低信頼ケースは最後に一覧表示する。
    （商品情報は管理画面で後から編集できる前提）
"""

import html
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
HTTPDOCS = ROOT / "httpdocs"
GARAGE_FILE = HTTPDOCS / "garage-file"
AERO_PARTS = HTTPDOCS / "aero-parts"
OUT_JSON = HTTPDOCS / "prototype" / "data" / "products.json"
OUT_SQL = HTTPDOCS / "prototype" / "data" / "products.sql"

# 元HTMLの <title> が誤っている等で自動抽出だと不適切になる商品の名称を手動補正する。
# （例: direct_ha36s_alto_works_alto_turbors は title が別商品からのコピペミス）
NAME_OVERRIDES = {
    "direct_ha36s_alto_works_alto_turbors": "【Black Edition】DIRect SUZUKI ALTO WORKS / TURBO RS",
}

# aero-parts-0N.html → カテゴリ名（f-left.html のメニュー定義を正とする）
CATEGORY_MAP = {
    "aero-parts-01": "乱人 / RANDO",
    "aero-parts-02": "RANDO Style",
    "aero-parts-03": "RANDO SPORTS",
    "aero-parts-04": "乱人流 SPORTS",
    "aero-parts-05": "Rando Ryu LUX",
    "aero-parts-06": "乱人 Black Edition",
    "aero-parts-07": "AVANT",
    "aero-parts-08": "DIRect",
}

COMMENT_RE = re.compile(r"<!--.*?-->", re.DOTALL)
TAG_RE = re.compile(r"<[^>]+>")
WS_RE = re.compile(r"[\s　]+")
TITLE_RE = re.compile(r"<title>(.*?)</title>", re.DOTALL | re.IGNORECASE)
FONT3_RE = re.compile(
    r'<font[^>]*size="\+3"[^>]*>(.*?)</font>', re.DOTALL | re.IGNORECASE
)
IMG_SRC_RE = re.compile(
    r'<img[^>]*\ssrc="([^"]*garage-img/[^"]+\.jpe?g)"', re.IGNORECASE
)
# 年式っぽい表記:（2021/10〜） / （H16.8〜H22.7） / 【1/2型】など
YEAR_RE = re.compile(r"[（(【][^（）()【】]*?[〜～年型期][^（）()【】]*?[）)】]")


def read_sjis(path: Path) -> str:
    """Shift_JIS として読み込む（壊れたバイトは置換して継続）。"""
    data = path.read_bytes()
    for enc in ("cp932", "shift_jis"):
        try:
            return data.decode(enc)
        except UnicodeDecodeError:
            continue
    return data.decode("cp932", errors="replace")


def strip_text(fragment: str) -> str:
    """HTML断片から表示テキストだけを取り出して整形する。"""
    fragment = fragment.replace("<br>", " ").replace("<br/>", " ").replace("<br />", " ")
    fragment = TAG_RE.sub("", fragment)
    fragment = html.unescape(fragment)
    fragment = WS_RE.sub(" ", fragment)
    return fragment.strip(" 　/")


def extract_name(text: str, slug: str) -> str:
    m = TITLE_RE.search(text)
    if m:
        name = strip_text(m.group(1))
        if name:
            return name
    m = FONT3_RE.search(text)
    if m:
        name = strip_text(m.group(1))
        if name:
            return name
    return slug


def extract_year(name: str, text: str) -> str:
    for source in (name, text):
        m = YEAR_RE.search(source)
        if m:
            return strip_text(m.group(0))
    return ""


def extract_images(text: str) -> list:
    """garage-img を指す img src を文書順・重複排除で返す（httpdocs 相対）。"""
    seen = set()
    images = []
    for src in IMG_SRC_RE.findall(text):
        rel = re.sub(r"^(\.\./)+", "", src)  # 先頭の ../ を除去
        rel = rel.split("?", 1)[0]
        if rel not in seen:
            seen.add(rel)
            images.append(rel)
    return images


def enlarged_name(image_dir: str, basename: str) -> str:
    """
    拡大画像のファイル名を返す。
    旧サイトは「先頭の 1 を 2 にした画像」が拡大版（例 1xxx01.jpg → 2xxx01.jpg）。
    実ファイルが存在すればそれを、無ければ表示画像をそのまま使う。
    """
    if basename.startswith("1"):
        candidate = "2" + basename[1:]
        if (HTTPDOCS / image_dir / candidate).exists():
            return candidate
    return basename


def find_spec_table(text: str) -> str:
    """bordercolor="#666666" を含む最初の <table>...</table> を入れ子対応で抜き出す。"""
    pos = text.lower().find('bordercolor="#666666"')
    if pos == -1:
        return ""
    start = text.lower().rfind("<table", 0, pos)
    if start == -1:
        return ""
    depth = 0
    i = start
    lower = text.lower()
    while i < len(text):
        if lower.startswith("<table", i):
            depth += 1
            i += 6
        elif lower.startswith("</table", i):
            depth -= 1
            i += 7
            if depth == 0:
                return text[start:i]
        else:
            i += 1
    return text[start:]


def extract_specs(text: str) -> list:
    table = find_spec_table(text)
    if not table:
        return []
    specs = []
    for tr in re.findall(r"<tr[^>]*>(.*?)</tr>", table, re.DOTALL | re.IGNORECASE):
        cells = [strip_text(td) for td in re.findall(
            r"<td[^>]*>(.*?)</td>", tr, re.DOTALL | re.IGNORECASE)]
        cells = [c for c in cells if c != ""]
        if not cells:
            continue
        if len(cells) == 1:
            specs.append({"label": cells[0], "value": ""})
        else:
            specs.append({"label": cells[0], "value": " / ".join(cells[1:])})
        if len(specs) >= 40:
            break
    return specs


def build_category_map() -> dict:
    """garage-file の slug → カテゴリ名（複数該当は ' / ' 連結）。"""
    slug_cats = {}
    link_re = re.compile(r"garage-file/([A-Za-z0-9_.\-]+)\.html", re.IGNORECASE)
    for stem, label in CATEGORY_MAP.items():
        path = AERO_PARTS / f"{stem}.html"
        if not path.exists():
            continue
        text = COMMENT_RE.sub("", read_sjis(path))
        for slug in dict.fromkeys(link_re.findall(text)):
            slug_cats.setdefault(slug, [])
            if label not in slug_cats[slug]:
                slug_cats[slug].append(label)
    return {slug: " / ".join(cats) for slug, cats in slug_cats.items()}


def main():
    category_map = build_category_map()
    products = {}
    low_confidence = []

    for path in sorted(GARAGE_FILE.glob("*.html")):
        slug = path.stem
        raw = read_sjis(path)
        text = COMMENT_RE.sub("", raw)

        name = NAME_OVERRIDES.get(slug) or extract_name(text, slug)
        images = extract_images(text)
        specs = extract_specs(text)
        image_dir = ""
        if images:
            image_dir = str(Path(images[0]).parent).replace("\\", "/")

        basenames = [Path(p).name for p in images]
        images_large = [enlarged_name(image_dir, b) for b in basenames]

        product = {
            "slug": slug,
            "name": name,
            "model_year": extract_year(name, text),
            "category": category_map.get(slug, "未分類"),
            "image_dir": image_dir,
            "images": basenames,
            "images_large": images_large,
            "specs": specs,
            "notes": "",
        }
        products[slug] = product

        if not images or not specs:
            low_confidence.append(
                f"  {slug}: images={len(images)} specs={len(specs)} name='{name}'")

    OUT_JSON.parent.mkdir(parents=True, exist_ok=True)
    OUT_JSON.write_text(
        json.dumps(products, ensure_ascii=False, indent=4), encoding="utf-8")

    write_sql(products)

    # ---- レポート ----
    total = len(products)
    with_img = sum(1 for p in products.values() if p["images"])
    with_spec = sum(1 for p in products.values() if p["specs"])
    with_cat = sum(1 for p in products.values() if p["category"] != "未分類")
    print(f"商品数: {total}")
    print(f"  画像あり : {with_img}")
    print(f"  SPECあり : {with_spec}")
    print(f"  カテゴリあり: {with_cat}（未分類 {total - with_cat}）")
    print(f"出力: {OUT_JSON}")
    print(f"出力: {OUT_SQL}")
    if low_confidence:
        print(f"\n要確認（画像かSPECが取得できなかった {len(low_confidence)} 件）:")
        print("\n".join(low_confidence))


def sql_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def write_sql(products: dict):
    lines = [
        "-- 自動生成（tools/migrate.py）。MariaDB へ全商品を投入する。",
        "-- 先に schema.sql でテーブルを作成してから実行すること。",
        "USE aerotech;",
        "",
    ]
    for i, p in enumerate(products.values()):
        images_json = sql_escape(json.dumps(p["images"], ensure_ascii=False))
        large_json = sql_escape(json.dumps(p["images_large"], ensure_ascii=False))
        specs_json = sql_escape(json.dumps(p["specs"], ensure_ascii=False))
        lines.append(
            "INSERT INTO products "
            "(slug, name, model_year, category, image_dir, images, images_large, specs, notes, sort_order) VALUES ("
            f"'{sql_escape(p['slug'])}', "
            f"'{sql_escape(p['name'])}', "
            f"'{sql_escape(p['model_year'])}', "
            f"'{sql_escape(p['category'])}', "
            f"'{sql_escape(p['image_dir'])}', "
            f"'{images_json}', "
            f"'{large_json}', "
            f"'{specs_json}', "
            f"'{sql_escape(p['notes'])}', "
            f"{i}) "
            "ON DUPLICATE KEY UPDATE name=VALUES(name), model_year=VALUES(model_year), "
            "category=VALUES(category), image_dir=VALUES(image_dir), images=VALUES(images), "
            "images_large=VALUES(images_large), specs=VALUES(specs);"
        )
    OUT_SQL.write_text("\n".join(lines) + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
