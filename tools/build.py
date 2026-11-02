# -*- coding: utf-8 -*-
"""LAICO DESIGN — site builder (v1.2.0).

Keeps the static template DRY without adding a runtime dependency:

* injects ONE shared header and footer into every page (same markup everywhere)
* rebuilds the hero of the six main pages (home, services, projects, journal,
  about, contact) with the common 100vh hero
* generates projects.html, project-*.html, journal.html and article-*.html
  from tools/site_data.py

Usage:   python3 tools/build.py
It is idempotent: running it twice produces the same files.
"""
import glob
import os
import re
import sys
from html import escape

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import site_data as D  # noqa: E402

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    Image = None

NAVY = "#0b1d33"

# ------------------------------------------------------------------ helpers
def read(name):
    with open(os.path.join(ROOT, name), encoding="utf8") as f:
        return f.read()


def write(name, text):
    with open(os.path.join(ROOT, name), "w", encoding="utf8", newline="\n") as f:
        f.write(text)


def bi(pair):
    fa, en = pair
    return f'<span class="lang-fa">{fa}</span><span class="lang-en" hidden>{en}</span>'


FA_DIGITS = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")


def fad(value):
    return str(value).translate(FA_DIGITS)


def num_pair(n, unit_fa, unit_en):
    return (f"{fad(f'{n:,}'.replace(',', '٬'))} {unit_fa}", f"{n:,} {unit_en}")


def g2j(gy, gm, gd):
    g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
    gy2 = gy + 1 if gm > 2 else gy
    days = 355666 + 365 * gy + (gy2 + 3) // 4 - (gy2 + 99) // 100 + (gy2 + 399) // 400 + gd + g_d_m[gm - 1]
    jy = -1595 + 33 * (days // 12053)
    days %= 12053
    jy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        jy += (days - 1) // 365
        days = (days - 1) % 365
    if days < 186:
        jm, jd = 1 + days // 31, 1 + days % 31
    else:
        jm, jd = 7 + (days - 186) // 30, 1 + (days - 186) % 30
    return jy, jm, jd


FA_MONTHS = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور", "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"]
EN_MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]


def date_pair(ymd):
    y, m, d = ymd
    jy, jm, jd = g2j(y, m, d)
    return (f"{fad(jd)} {FA_MONTHS[jm - 1]} {fad(jy)}", f"{d} {EN_MONTHS[m - 1]} {y}")


_dims = {}


def dims(src):
    if src not in _dims:
        w = h = None
        path = os.path.join(ROOT, src)
        if Image and os.path.exists(path):
            with Image.open(path) as im:
                w, h = im.size
        _dims[src] = (w, h)
    return _dims[src]


def img(src, alt_pair=None, cls="", lazy=True, extra=""):
    w, h = dims(src)
    attrs = [f'src="{src}"']
    if alt_pair:
        attrs.append(f'alt="{alt_pair[0]}" data-alt-fa="{alt_pair[0]}" data-alt-en="{alt_pair[1]}"')
    else:
        attrs.append('alt=""')
    if w and h:
        attrs.append(f'width="{w}" height="{h}"')
    if cls:
        attrs.append(f'class="{cls}"')
    if lazy:
        attrs.append('loading="lazy" decoding="async"')
    if extra:
        attrs.append(extra)
    return "<img " + " ".join(attrs) + ">"


# ------------------------------------------------------------------ shared header / footer
NAV = [
    ("index.html", "home", "nav-home", "صفحه اصلی"),
    ("services.html", "services", "nav-services", "خدمات"),
    ("projects.html", "projects", "nav-projects", "پروژه‌ها"),
    ("journal.html", "journal", "nav-journal", "مجله"),
    ("about.html", "about", "nav-about", "درباره ما"),
    ("contact.html", "contact", "nav-contact", "تماس با ما"),
]


def header_html(cta="contact.html"):
    links = "\n".join(
        f'        <a href="{h}" data-nav="{k}" data-i18n="{i}">{t}</a>' for h, k, i, t in NAV
    )
    panel = "\n".join(
        f'    <a href="{h}" data-nav="{k}" data-i18n="{i}">{t}</a>' for h, k, i, t in NAV
    )
    return f"""<header class="site-header" data-header>
  <div class="header-bar">
    <div class="header-start">
      <a class="logo" href="index.html" aria-label="Laico Design">
        <img src="assets/logo2.png" alt="Laico Design" width="357" height="232">
      </a>
      <nav class="desk-nav" aria-label="main">
{links}
      </nav>
    </div>
    <div class="header-tools">
      <div class="lang" role="group" aria-label="Language">
        <button type="button" data-lang="fa" class="is-active">FA</button>
        <span aria-hidden="true">/</span>
        <button type="button" data-lang="en">EN</button>
      </div>
      <a class="btn-pill header-cta" href="{cta}" data-i18n="cta">درخواست مشاوره</a>
      <button class="burger" type="button" data-burger aria-expanded="false" aria-controls="nav-panel" data-i18n-aria="menu" aria-label="منو">
        <span></span><span></span>
      </button>
    </div>
  </div>
  <div class="nav-panel" id="nav-panel">
{panel}
    <a class="btn-pill" href="{cta}" data-i18n="cta">درخواست مشاوره</a>
  </div>
</header>"""


SOCIAL = {
    "wa": ("https://wa.me/989120898938", "واتس‌اپ",
           "M12.04 3.2A8.7 8.7 0 0 0 4.7 16.3L3.6 20.4l4.2-1.1a8.7 8.7 0 0 0 4.24 1.08h.01a8.7 8.7 0 0 0 0-17.4zm0 15.9h-.01a7.2 7.2 0 0 1-3.67-.99l-.26-.16-2.49.65.66-2.43-.17-.25a7.22 7.22 0 1 1 5.94 3.18zm3.96-5.4c-.22-.11-1.28-.63-1.48-.7-.2-.07-.34-.11-.49.11-.14.22-.56.7-.68.84-.13.14-.25.16-.47.05-.22-.11-.92-.34-1.75-1.08-.65-.58-1.08-1.29-1.21-1.51-.13-.22-.01-.34.1-.45.1-.1.22-.25.33-.38.11-.13.14-.22.22-.36.07-.14.04-.27-.02-.38-.07-.11-.49-1.18-.67-1.62-.18-.43-.36-.37-.49-.38h-.42c-.14 0-.38.05-.58.27-.2.22-.76.74-.76 1.8s.78 2.09.89 2.23c.11.14 1.53 2.34 3.71 3.28.52.22.92.36 1.24.46.52.17.99.14 1.36.09.42-.06 1.28-.52 1.46-1.03.18-.5.18-.94.13-1.03-.05-.09-.2-.14-.42-.25z"),
    "tg": ("https://t.me/Laico_design", "تلگرام",
           "M20.7 4.3 3.4 10.9c-1.2.46-1.18 1.1-.22 1.38l4.42 1.38 10.23-6.45c.48-.3.93-.13.56.19l-8.28 7.48-.32 4.55c.46 0 .67-.21.92-.46l2.22-2.16 4.62 3.41c.85.47 1.46.23 1.67-.79l3.03-14.27c.31-1.24-.47-1.8-1.28-1.46z"),
    "ig": ("https://www.instagram.com/Laico_design/", "اینستاگرام",
           "M12 7.4A4.6 4.6 0 1 0 12 16.6 4.6 4.6 0 0 0 12 7.4zm0 7.6a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm5.85-8.55a1.08 1.08 0 1 1-2.16 0 1.08 1.08 0 0 1 2.16 0zM12 4.8c1.35 0 1.51.01 2.04.03.49.02.76.1 1.04.18.28.08.48.18.69.33.21.15.37.31.52.52.15.21.25.41.33.69.08.28.16.55.18 1.04.02.53.03.69.03 2.04s-.01 1.51-.03 2.04c-.02.49-.1.76-.18 1.04-.08.28-.18.48-.33.69-.15.21-.31.37-.52.52-.21.15-.41.25-.69.33-.28.08-.55.16-1.04.18-.53.02-.69.03-2.04.03s-1.51-.01-2.04-.03c-.49-.02-.76-.1-1.04-.18a1.8 1.8 0 0 1-.69-.33 1.8 1.8 0 0 1-.52-.52 1.8 1.8 0 0 1-.33-.69c-.08-.28-.16-.55-.18-1.04-.02-.53-.03-.69-.03-2.04s.01-1.51.03-2.04c.02-.49.1-.76.18-1.04.08-.28.18-.48.33-.69.15-.21.31-.37.52-.52.21-.15.41-.25.69-.33.28-.08.55-.16 1.04-.18.53-.02.69-.03 2.04-.03zm0-1.6c-1.37 0-1.54.01-2.08.03-.55.02-.93.11-1.26.24-.34.13-.63.31-.92.6-.29.29-.47.58-.6.92-.13.33-.22.71-.24 1.26-.02.54-.03.71-.03 2.08s.01 1.54.03 2.08c.02.55.11.93.24 1.26.13.34.31.63.6.92.29.29.58.47.92.6.33.13.71.22 1.26.24.54.02.71.03 2.08.03s1.54-.01 2.08-.03c.55-.02.93-.11 1.26-.24.34-.13.63-.31.92-.6.29-.29.47-.58.6-.92.13-.33.22-.71.24-1.26.02-.54.03-.71.03-2.08s-.01-1.54-.03-2.08c-.02-.55-.11-.93-.24-1.26a2.6 2.6 0 0 0-.6-.92 2.6 2.6 0 0 0-.92-.6c-.33-.13-.71-.22-1.26-.24-.54-.02-.71-.03-2.08-.03z"),
}


def footer_html():
    soc = "\n".join(
        f'        <a class="soc" href="{u}" target="_blank" rel="noopener" aria-label="{n}">\n'
        f'          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{p}"/></svg>\n        </a>'
        for u, n, p in SOCIAL.values()
    )
    links = [
        ("services.html", ("طراحی پلان", "Space planning")),
        ("kitchen.html", ("آشپزخانه", "Kitchen")),
        ("bath.html", ("سرویس و حمام", "Bath")),
        ("living.html", ("پذیرایی و اتاق خواب", "Living & bedroom")),
        ("cafe.html", ("کافه و رستوران", "Café & restaurant")),
    ]
    fl = "\n".join(f'      <a href="{h}">{bi(t)}</a>' for h, t in links)
    return f"""<footer class="site-footer">
  <div class="footer-main">
    <nav class="footer-links" aria-label="footer">
{fl}
    </nav>
    <div class="footer-brand">
      <a class="footer-logo" href="index.html" aria-label="Laico Design">
        <img src="assets/logo2.png" alt="Laico Design" width="357" height="232" loading="lazy">
      </a>
      <p>{bi(("لایکودیزاین مجموعه‌ی معماری و طراحی داخلی", "Laico Design — architecture and interior design"))}</p>
      <div class="footer-social">
{soc}
      </div>
    </div>
    <div class="footer-contact">
      <p>{bi(("مازندران، آمل، دریای ۲۰، لایکودیزاین", "Mazandaran, Amol, Darya 20, Laico Design"))}</p>
      <a class="footer-phone" href="tel:09120898938">09120898938</a>
    </div>
  </div>
  <div class="footer-legal">
    <p>{bi(("تمامی حقوق این سایت محفوظ می‌باشد.", "All rights reserved."))}</p>
    <nav class="footer-legal-links" aria-label="legal">
      <a href="privacy.html" data-i18n="privacy">حریم خصوصی</a>
      <a href="cookies.html" data-i18n="cookies">کوکی‌ها</a>
    </nav>
    <p>{bi(("طراحی و اجرا آمل نوین", "Designed and built by Amol Novin"))}</p>
  </div>
</footer>"""


# ------------------------------------------------------------------ hero
def hero(image, kicker, title, lede, target, cls="", inner="", hint=True, alt=None):
    w, h = dims(image)
    size = f' width="{w}" height="{h}"' if w else ""
    hint_html = (
        f'\n  <a class="scroll-hint" href="#{target}" dir="ltr" aria-label="Scroll"><span>SCROLL</span><i></i></a>'
        if hint else ""
    )
    return f"""<section class="hero hero--page {cls}" id="top">
  <div class="hero-media">
    <img src="{image}" alt=""{size} fetchpriority="high">
  </div>
  <div class="hero-shade"></div>
  <div class="hero-copy">
    <p class="hero-kicker">{bi(kicker)}</p>
    <h1>{bi(title)}</h1>
    <div class="hero-rule"></div>
    <p class="lede">{bi(lede)}</p>
  </div>{inner}{hint_html}
</section>"""


# ------------------------------------------------------------------ page shell for generated pages
def head(title, desc, page, css=("css/styles.css",), og_image=None):
    fa, en = title
    links = "\n".join(f'<link rel="stylesheet" href="{c}">' for c in css)
    return f"""<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{fa}</title>
<meta name="description" content="{escape(desc[0], quote=True)}">
<meta name="theme-color" content="{NAVY}">
<meta property="og:title" content="{fa}">
<meta property="og:description" content="{escape(desc[0], quote=True)}">
<meta property="og:type" content="website">
<meta property="og:locale" content="fa_IR">
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="preload" href="assets/fonts/Vazirmatn-Regular.woff2" as="font" type="font/woff2" crossorigin>
{links}
<script>
(function(){{try{{var l=localStorage.getItem("laico-lang");if(l==="en"){{document.documentElement.lang="en";document.documentElement.dir="ltr";}}}}catch(e){{}}}})();
</script>
</head>
<body data-page="{page}" data-title-fa="{fa}" data-title-en="{en}">
"""


def tail(scripts=("js/main.js",)):
    s = "\n".join(f'<script src="{x}"></script>' for x in scripts)
    return f"\n{s}\n</body>\n</html>\n"


def page(title, desc, page_key, main, css=("css/styles.css",), scripts=("js/main.js",), cta="contact.html"):
    return (
        head(title, desc, page_key, css)
        + header_html(cta)
        + '\n<main id="main">\n'
        + main
        + "\n</main>\n\n"
        + footer_html()
        + tail(scripts)
    )


# ------------------------------------------------------------------ consult form (shared by services / projects)
def consult_block():
    src = read("services.html")
    m = re.search(r'<section class="svc-consult".*?</section>', src, re.S)
    return m.group(0)


# ------------------------------------------------------------------ projects page
def prj_size(area):
    return "small" if area < 120 else ("medium" if area <= 300 else "large")


def project_card(p):
    t = p["title"]
    keywords = " ".join([t[0], t[1], D.CITY[p["city"]][0], D.CITY[p["city"]][1], D.SPACE[p["space"]][0], D.SPACE[p["space"]][1]]).lower()
    alt = (f'{t[0]} — {D.SPACE[p["space"]][0]}', f'{t[1]} — {D.SPACE[p["space"]][1]}')
    return f"""    <a class="prj-card" data-prj-card data-category="{p['cat']}" data-space="{p['space']}" data-city="{p['city']}" data-year="{p['year']}" data-size="{prj_size(p['area'])}" data-q="{escape(keywords, quote=True)}" href="project-{p['slug']}.html">
      {img(f"assets/images/{p['main']}.jpg", alt)}
      <span class="prj-shade" aria-hidden="true"></span>
      <span class="prj-tag">{bi(D.SPACE[p['space']])}</span>
      <span class="prj-view">{bi(("مشاهده پروژه", "View project"))}</span>
      <span class="prj-title">{bi(t)}</span>
    </a>"""


def projects_page():
    cards = "\n\n".join(project_card(p) for p in D.PROJECTS)
    spaces = "\n".join(
        f'      <button type="button" class="prj-pill" data-space-filter="{k}">{bi(v)}</button>'
        for k, v in D.SPACE.items()
    )
    cities = "\n".join(
        f'            <option value="{k}" data-fa="{v[0]}" data-en="{v[1]}">{v[0]}</option>' for k, v in D.CITY.items()
    )
    years = "\n".join(
        f'            <option value="{k}" data-fa="{fad(k)}" data-en="{v}">{fad(k)}</option>' for k, v in D.YEAR.items()
    )
    hero_html = hero(
        "assets/images/hero-projects.jpg",
        ("آرشیو پروژه‌ها", "Project archive"),
        ("همه پروژه‌ها، یک استاندارد ساخت", "All projects, one standard of making"),
        ("مجموعهٔ کامل پروژه‌های اجراشدهٔ لایکو دیزاین در فضاهای مسکونی، تجاری و کافه-رستوران.",
         "The complete archive of Laico Design projects in residential, commercial and café-restaurant spaces."),
        "prj-list",
    )
    return f"""
{hero_html}

<section class="prj-filters" id="prj-list" aria-label="فیلتر پروژه‌ها">
  <div class="prj-filters-inner">
    <div class="prj-filter-top">
      <div class="prj-tabs" role="group" aria-label="دسته‌بندی">
        <button type="button" class="prj-tab is-active" data-category="all">{bi(("همه پروژه‌ها", "All projects"))}</button>
        <button type="button" class="prj-tab" data-category="residential">{bi(D.CATEGORY['residential'])}</button>
        <button type="button" class="prj-tab" data-category="commercial">{bi(D.CATEGORY['commercial'])}</button>
      </div>
      <div class="prj-search">
        <label class="visually-hidden" for="prj-search-input">{bi(("جستجوی پروژه", "Search projects"))}</label>
        <input id="prj-search-input" type="search" data-prj-search data-ph-fa="جستجوی پروژه…" data-ph-en="Search projects…" placeholder="جستجوی پروژه…" autocomplete="off">
      </div>
    </div>

    <div class="prj-spaces" role="group" aria-label="نوع فضا">
      <button type="button" class="prj-pill is-active" data-space-filter="all">{bi(("همه فضاها", "All spaces"))}</button>
{spaces}
    </div>

    <div class="prj-selects">
      <label class="prj-select-group">
        <span class="prj-select-label">{bi(("موقعیت", "Location"))}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="city">
            <option value="all" data-fa="همه شهرها" data-en="All cities">همه شهرها</option>
{cities}
          </select>
        </span>
      </label>
      <label class="prj-select-group">
        <span class="prj-select-label">{bi(("سال", "Year"))}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="year">
            <option value="all" data-fa="همه سال‌ها" data-en="All years">همه سال‌ها</option>
{years}
          </select>
        </span>
      </label>
      <label class="prj-select-group">
        <span class="prj-select-label">{bi(("متراژ", "Area"))}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="size">
            <option value="all" data-fa="همه ابعاد" data-en="All sizes">همه ابعاد</option>
            <option value="small" data-fa="کوچک (زیر ۱۲۰ متر)" data-en="Small (under 120 m²)">کوچک (زیر ۱۲۰ متر)</option>
            <option value="medium" data-fa="متوسط (۱۲۰ تا ۳۰۰ متر)" data-en="Medium (120–300 m²)">متوسط (۱۲۰ تا ۳۰۰ متر)</option>
            <option value="large" data-fa="بزرگ (بالای ۳۰۰ متر)" data-en="Large (over 300 m²)">بزرگ (بالای ۳۰۰ متر)</option>
          </select>
        </span>
      </label>
    </div>
  </div>
</section>

<div class="prj-results">
  <h2>{bi(("نتایج جستجو", "Results"))}</h2>
  <span class="prj-count" data-prj-count></span>
</div>

<section class="prj-grid-wrap" aria-label="پروژه‌ها">
  <div class="prj-grid" data-prj-grid data-per-page="9">

{cards}

  </div>
  <div class="prj-empty" data-prj-empty hidden>{bi(("پروژه‌ای با این فیلترها یافت نشد.", "No projects match these filters."))}</div>
  <nav class="pager" data-prj-pager aria-label="Pagination" hidden></nav>
</section>

{consult_block()}
"""


# ------------------------------------------------------------------ project detail pages
def spec_row(label, value):
    return f'      <div class="pd-spec"><dt>{bi(label)}</dt><dd>{bi(value)}</dd></div>'


def project_page(i, p):
    t = p["title"]
    prev_p = D.PROJECTS[(i - 1) % len(D.PROJECTS)]
    next_p = D.PROJECTS[(i + 1) % len(D.PROJECTS)]
    area = num_pair(p["area"], "متر مربع", "m²")
    year = (fad(p["year"]), D.YEAR[p["year"]])
    specs = "\n".join([
        spec_row(("کارفرما", "Client"), p["client"]),
        spec_row(("سال انجام پروژه", "Year"), year),
        spec_row(("مدت زمان پروژه", "Duration"), p["duration"]),
        spec_row(("مکان پروژه", "Location"), p["place"]),
        spec_row(("متراژ پروژه", "Area"), area),
    ])
    paras = "\n".join(f"    <p>{bi(x)}</p>" for x in p["text"])
    gallery = "\n".join(
        f'      <a class="pd-shot" href="assets/images/{g}.jpg" data-gallery-item aria-label="{t[0]}">'
        f'{img(f"assets/images/{g}.jpg", (f"{t[0]} — تصویر {fad(n + 1)}", f"{t[1]} — image {n + 1}"))}</a>'
        for n, g in enumerate(p["gallery"])
    )
    main_alt = (f"{t[0]} — تصویر اصلی", f"{t[1]} — main image")
    main = f"""
<section class="pd-top">
  <div class="pd-wrap">
    <nav class="crumbs" aria-label="breadcrumb">
      <a href="projects.html">{bi(("پروژه‌ها", "Projects"))}</a><span aria-hidden="true">/</span><span>{bi(t)}</span>
    </nav>
    <p class="pd-kicker">{bi(D.CATEGORY[p['cat']])} · {bi(D.SPACE[p['space']])}</p>
    <h1>{bi(t)}</h1>
    <p class="pd-lede">{bi(p['lede'])}</p>
  </div>
</section>

<section class="pd-wrap pd-main">
  <figure class="pd-cover">
    {img(f"assets/images/{p['main']}.jpg", main_alt, lazy=False, extra='fetchpriority="high"')}
  </figure>
  <aside class="pd-card" aria-label="{t[0]}">
    <h2>{bi(("مشخصات پروژه", "Project details"))}</h2>
    <dl class="pd-specs">
{specs}
    </dl>
    <a class="btn-solid" href="contact.html">{bi(("درخواست مشاوره برای پروژه مشابه", "Ask about a similar project"))}</a>
  </aside>
</section>

<section class="pd-wrap pd-about">
  <h2>{bi(("دربارهٔ پروژه", "About the project"))}</h2>
  <div class="pd-text">
{paras}
  </div>
</section>

<section class="pd-wrap pd-gallery-sec" aria-labelledby="gal-title">
  <h2 id="gal-title">{bi(("گالری", "Gallery"))}</h2>
  <div class="pd-gallery" data-gallery>
{gallery}
  </div>
</section>

<section class="pd-wrap pd-video-sec" aria-labelledby="vid-title">
  <h2 id="vid-title">{bi(("ویدئو پروژه", "Project video"))}</h2>
  <div class="pd-video">
    <video controls preload="none" playsinline poster="assets/images/{p['main']}.jpg">
      <source src="assets/video/tour-{p['video']}.mp4" type="video/mp4">
    </video>
  </div>
</section>

<nav class="pd-pager" aria-label="{t[0]}">
  <a class="pd-pager-link" href="project-{prev_p['slug']}.html" rel="prev">
    <span class="pd-pager-label">{bi(("پروژهٔ قبلی", "Previous project"))}</span>
    <strong>{bi(prev_p['title'])}</strong>
  </a>
  <a class="pd-pager-all btn-pill-dark" href="projects.html">{bi(("همهٔ پروژه‌ها", "All projects"))}</a>
  <a class="pd-pager-link pd-pager-next" href="project-{next_p['slug']}.html" rel="next">
    <span class="pd-pager-label">{bi(("پروژهٔ بعدی", "Next project"))}</span>
    <strong>{bi(next_p['title'])}</strong>
  </a>
</nav>
"""
    return page(
        (f"{t[0]} — لایکو دیزاین", f"{t[1]} — Laico Design"),
        (p["lede"][0], p["lede"][1]),
        "projects",
        main,
        scripts=("js/main.js", "js/project.js"),
    )


# ------------------------------------------------------------------ journal
def article_card(a, featured=False):
    t = a["title"]
    meta = f'<span>{bi(a["cat"])}</span><time datetime="{a["date"][0]}-{a["date"][1]:02d}-{a["date"][2]:02d}">{bi(date_pair(a["date"]))}</time><span>{bi((fad(a["read"]) + " دقیقه مطالعه", str(a["read"]) + " min read"))}</span>'
    cls = "jr-card jr-card--featured" if featured else "jr-card"
    return f"""    <a class="{cls}" href="article-{a['slug']}.html">
      <span class="jr-media">{img(f"assets/images/{a['image']}.jpg", t, lazy=not featured)}</span>
      <span class="jr-body">
        <span class="jr-meta">{meta}</span>
        <h{2 if featured else 3} class="jr-title">{bi(t)}</h{2 if featured else 3}>
        <span class="jr-excerpt">{bi(a['excerpt'])}</span>
        <span class="jr-more">{bi(("ادامهٔ مطلب", "Read the article"))}</span>
      </span>
    </a>"""


def journal_page():
    hero_html = hero(
        "assets/images/hero-journal.jpg",
        ("مجله لایکو", "Laico Journal"),
        ("یادداشت‌های ساخت", "Notes on making"),
        ("دربارهٔ متریال، نور، پلان و جزئیاتی که یک فضا را ماندگار می‌کنند.",
         "On materials, light, planning and the details that make a space last."),
        "journal",
    )
    first, rest = D.ARTICLES[0], D.ARTICLES[1:]
    grid = "\n".join(article_card(a) for a in rest)
    return f"""
{hero_html}

<section class="jr" id="journal">
  <div class="jr-wrap">
    <div class="jr-head">
      <p class="eyebrow">{bi(("آخرین یادداشت", "Latest"))}</p>
    </div>
{article_card(first, True)}
    <div class="jr-head jr-head--grid">
      <h2>{bi(("همهٔ یادداشت‌ها", "All notes"))}</h2>
    </div>
    <div class="jr-grid">
{grid}
    </div>
  </div>
</section>
"""


def article_page(i, a):
    t = a["title"]
    others = [x for x in D.ARTICLES if x is not a]
    rel = [others[(i + k) % len(others)] for k in range(2)]
    blocks = []
    for kind, val in a["body"]:
        if kind == "p":
            blocks.append(f"    <p>{bi(val)}</p>")
        elif kind == "h2":
            blocks.append(f"    <h2>{bi(val)}</h2>")
        else:
            blocks.append(f"    <blockquote>{bi(val)}</blockquote>")
    body = "\n".join(blocks)
    related = "\n".join(article_card(x) for x in rel)
    meta = f'<span>{bi(a["cat"])}</span><time datetime="{a["date"][0]}-{a["date"][1]:02d}-{a["date"][2]:02d}">{bi(date_pair(a["date"]))}</time><span>{bi((fad(a["read"]) + " دقیقه مطالعه", str(a["read"]) + " min read"))}</span>'
    main = f"""
<section class="pd-top ar-top">
  <div class="ar-wrap">
    <nav class="crumbs" aria-label="breadcrumb">
      <a href="journal.html">{bi(("مجله", "Journal"))}</a><span aria-hidden="true">/</span><span>{bi(a['cat'])}</span>
    </nav>
    <h1>{bi(t)}</h1>
    <p class="ar-meta">{meta}</p>
  </div>
</section>

<article class="ar">
  <figure class="ar-cover">
    {img(f"assets/images/{a['image']}.jpg", t, lazy=False, extra='fetchpriority="high"')}
  </figure>
  <div class="ar-body">
    <p class="ar-lead">{bi(a['excerpt'])}</p>
{body}
    <a class="btn-line ar-back" href="journal.html">{bi(("بازگشت به مجله", "Back to the journal"))}</a>
  </div>
</article>

<section class="jr jr--related">
  <div class="jr-wrap">
    <div class="jr-head jr-head--grid"><h2>{bi(("بیشتر بخوانید", "Keep reading"))}</h2></div>
    <div class="jr-grid jr-grid--two">
{related}
    </div>
  </div>
</section>
"""
    return page(
        (f"{t[0]} — مجله لایکو", f"{t[1]} — Laico Journal"),
        a["excerpt"],
        "journal",
        main,
    )


# ------------------------------------------------------------------ transforms for existing pages
def swap_header_footer(html, cta):
    html = re.sub(r'<a class="skip".*?</a>\s*', "", html, flags=re.S)
    html = re.sub(r"<header class=\"site-header\".*?</header>", lambda m: header_html(cta), html, flags=re.S)
    html = re.sub(r"<footer class=\"site-footer\">.*?</footer>", lambda m: footer_html(), html, flags=re.S)
    html = re.sub(r'<meta name="theme-color" content="[^"]*">', f'<meta name="theme-color" content="{NAVY}">', html)
    # one main.js per page
    scripts = re.findall(r'<script src="js/main.js"></script>\n?', html)
    if len(scripts) > 1:
        first = html.index(scripts[0])
        head_part, rest = html[: first + len(scripts[0])], html[first + len(scripts[0]):]
        html = head_part + rest.replace(scripts[0], "")
    # old placeholder phone number
    html = html.replace("tel:+98210000000", "tel:+989120898938").replace("+98 21 000 0000", "+98 912 089 8938")
    return html


def sub_section(html, pattern, repl):
    new, n = re.subn(pattern, lambda m: repl, html, count=1, flags=re.S)
    return new if n else html


def transform_existing(name):
    html = read(name)
    cta = "#collaborate" if name == "index.html" else "contact.html"
    html = swap_header_footer(html, cta)

    if name == "services.html":
        html = sub_section(
            html, r'(?:<!--[^>]*-->\s*)?<section class="svc-hero".*?</section>',
            hero("assets/images/hero-services.jpg",
                 ("خدمات", "Services"),
                 ("خدمات طراحی لایکودیزاین", "Laico Design Services"),
                 ("طراحی فضاهای کافه و رستوران، تفریحی، درمانی، باشگاهی و ...",
                  "Cafés, restaurants, leisure, clinical and sports spaces — designed and built by one team."),
                 "svc-list"),
        )
    elif name == "about.html":
        html = sub_section(
            html, r'<section class="about-hero">.*?</section>',
            hero("assets/images/hero-about.jpg",
                 ("درباره ما", "About us"),
                 ("درباره لایکودیزاین، از ایده تا اجرا", "About Laico Design, from idea to build"),
                 ("استودیوی معماری داخلی و سازندهٔ سیستم آشپزخانه؛ طراحی و اجرا در یک تیم.",
                  "An interior architecture studio and kitchen-system maker; design and build in one team."),
                 "about-story"),
        )
        html = html.replace('<section class="about-story">', '<section class="about-story" id="about-story">')
    elif name == "contact.html":
        m = re.search(r'<section class="contact-hero">.*?(<div class="contact-card">.*?</div>)\s*</section>', html, re.S)
        if m:
            card = m.group(1)
            html = html.replace(
                m.group(0),
                hero("assets/images/hero-contact.jpg",
                     ("تماس با ما", "Contact"),
                     ("بیایید دربارهٔ فضای شما صحبت کنیم", "Let's talk about your space"),
                     ("برای مشاوره، بازدید شو روم یا شروع پروژه با ما در ارتباط باشید.",
                      "Get in touch for a consultation, a showroom visit or to start a project."),
                     "contact-map", cls="hero--contact", inner="\n  " + card, hint=False),
            )
    elif name in ("privacy.html", "cookies.html"):
        html = re.sub(r'<body data-page="[^"]*"', '<body data-page="legal"', html)
        html = html.replace(' style="padding-top:120px"', "")
    write(name, html)


MOTION_HEAD = (
    '<link rel="stylesheet" href="css/motion.css">\n'
    '<script>(function(){try{if(!window.matchMedia("(prefers-reduced-motion: reduce)").matches)'
    'document.documentElement.classList.add("mo-on");}catch(e){}})();</script>\n'
)


def add_motion():
    """Motion layer (v1.3.0): stylesheet + head flag on every page, motion.js after main.js. Idempotent."""
    for path in glob.glob(os.path.join(ROOT, "*.html")):
        html = open(path, encoding="utf8").read()
        if "css/motion.css" in html:
            continue
        links = list(re.finditer(r'<link rel="stylesheet" href="[^"]+">\n?', html))
        end = links[-1].end()
        html = html[:end] + MOTION_HEAD + html[end:]
        html = re.sub(r'(<script src="js/main.js"></script>\n?)', r'\1<script src="js/motion.js" defer></script>\n', html, count=1)
        open(path, "w", encoding="utf8").write(html)


def main():
    # 1. generated pages
    write("projects.html", page(
        ("همه پروژه‌ها، یک استاندارد ساخت — لایکو دیزاین", "All Projects — Laico Design"),
        ("مجموعهٔ کامل پروژه‌های اجراشدهٔ لایکو دیزاین در فضاهای مسکونی، تجاری و کافه-رستوران.", ""),
        "projects", projects_page(),
        css=("css/styles.css", "css/services.css", "css/projects.css"),
        scripts=("js/main.js", "js/projects.js"),
    ))
    for i, p in enumerate(D.PROJECTS):
        write(f"project-{p['slug']}.html", project_page(i, p))
    write("journal.html", page(
        ("یادداشت‌های ساخت — مجله لایکو", "Notes on making — Laico Journal"),
        ("یادداشت‌هایی دربارهٔ متریال، نور، پلان و جزئیات ساخت از استودیوی لایکو دیزاین.", ""),
        "journal", journal_page(),
    ))
    for i, a in enumerate(D.ARTICLES):
        write(f"article-{a['slug']}.html", article_page(i, a))
    # 2. existing pages
    generated = {"projects.html", "journal.html"} | {f"project-{p['slug']}.html" for p in D.PROJECTS} | {
        f"article-{a['slug']}.html" for a in D.ARTICLES}
    for path in sorted(glob.glob(os.path.join(ROOT, "*.html"))):
        name = os.path.basename(path)
        if name in generated:
            continue
        transform_existing(name)
    add_motion()
    print("built", len(glob.glob(os.path.join(ROOT, "*.html"))), "pages")


if __name__ == "__main__":
    main()
