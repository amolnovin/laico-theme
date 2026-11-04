{{--
  Template Name: لایکو — صفحه مجموعه / زیرصفحه تخصصی
--}}
@extends('layouts.app')

@section('content')
  @php
    $postObj = get_queried_object();
    $pageId = $postObj instanceof \WP_Post ? (int) $postObj->ID : 0;
    $slug = $postObj instanceof \WP_Post ? (string) $postObj->post_name : 'kitchen';

    $presets = [
      'kitchen' => [
        'eye' => ['۰۱ — مجموعه‌ها', '01 — Collections'],
        'title' => ['آشپزخانه', 'Kitchen'],
        'lede' => ['سیستم بدون دستگیره، صفحه سنگ، و جزئیاتی که بعد از سال‌ها هنوز بسته می‌مانند.', 'A handleless system, stone worktops, and details that still close years later.'],
        'img' => 'images/proj-aftab.jpg',
        'h2' => ['یک حجم، نه یک مجموعه کابینت', 'One volume, not a run of cabinets'],
        'p1' => ['آشپزخانه لایکو از پروفیل آلومینیوم و درِ لاک مات ساخته می‌شود. درزها هم‌ترازند، دستگیره حذف شده، و صفحه سنگ تا کف جزیره ادامه پیدا می‌کند.', 'A Laico kitchen is built from aluminium profiles and matte-lacquer doors. Joints sit flush, handles disappear, and the stone continues down the island.'],
        'p2' => ['پلان، نور، و مسیر کار پیش از انتخاب رنگ بسته می‌شود. رنگ، آخرین تصمیم است؛ ساخت، اولین تعهد.', 'Plan, light, and the working path are settled before colour. Colour is the last decision. Making is the first commitment.'],
        'points' => [
          ['01', 'پروفیل', 'Profile', 'فریم آلومینیومی با تلورانس زیر یک میلی‌متر.', 'An aluminium frame, tolerance under one millimetre.'],
          ['02', 'صفحه', 'Surface', 'سنگ طبیعی یا گرس پرسلان، لبه تمام‌شده در کارگاه.', 'Natural stone or gres porcelain, edges finished in the workshop.'],
          ['03', 'حرکت', 'Motion', 'لولای آرام‌بند و ریل فول بلوم، بی‌صدا.', 'Soft-close hinges and Blum full-extension runners, quiet.'],
        ],
      ],
      'living' => [
        'eye' => ['۰۱ — مجموعه‌ها', '01 — Collections'],
        'title' => ['پذیرایی و اتاق خواب', 'Living & bedroom'],
        'lede' => ['کتابخانه، کمد و دیوارکوب با همان سیستم و همان تلورانس آشپزخانه.', 'Bookcases, wardrobes and panelling built on the same system and tolerance as the kitchen.'],
        'img' => 'images/col-living.jpg',
        'h2' => ['امتداد معماری در اتاق‌های خانه', 'Architecture carried into every room'],
        'p1' => ['کمدها و قفسه‌های نشیمن در لایکو مبلمان جداگانه نیستند؛ بخشی از دیوار و معماری فضا هستند که سیم‌کشی، نور و ذخیره‌سازی را پنهان می‌کنند.', 'Living systems and wardrobes at Laico are not loose furniture; they form architectural walls that conceal wiring, light and storage.'],
        'p2' => ['پالت روکش‌های چوب راش و لاک سرمه‌ای باعث می‌شود آشپزخانه، نشیمن و اتاق خواب یک زبان آرام و پیوسته داشته باشند.', 'Shared beech veneers and matte navy finishes keep the kitchen, living room and bedrooms speaking one quiet language.'],
        'points' => [
          ['01', 'یکپارچگی', 'Continuity', 'هم‌ترازی کامل کمدها از کف تا سقف بدون قاب اضافه.', 'Floor-to-ceiling alignment without trim clutter.'],
          ['02', 'نور داخلی', 'Integrated light', 'نور خطی گرم داخل قفسه‌ها با سنسور نامرئی.', 'Warm linear shelf lighting with concealed sensors.'],
          ['03', 'سازمان‌دهی', 'Internal order', 'کشوها و تقسیم‌بندی‌های چوبی سفارشی.', 'Bespoke timber drawer dividers and interior fittings.'],
        ],
      ],
      'bath' => [
        'eye' => ['۰۱ — مجموعه‌ها', '01 — Collections'],
        'title' => ['سرویس و حمام', 'Bath'],
        'lede' => ['سنگ یکپارچه، کابینت ضدآب، و نوری که صبح را آرام شروع می‌کند.', 'Continuous stone, moisture-sealed cabinetry, and light that starts the morning calmly.'],
        'img' => 'images/col-bath.jpg',
        'h2' => ['جزئیات مقاوم در برابر رطوبت', 'Moisture-proof architectural joinery'],
        'p1' => ['حمام‌های لایکو با بدنهٔ مقاوم در برابر بخار، صفحات سنگ بدون درز و آینه‌های پشت‌نورخورده ساخته می‌شوند.', 'Laico bathrooms pair vapour-sealed carcasses with seamless stone basins and shadow-free backlit mirrors.'],
        'p2' => ['کابینت‌های معلق نظافت کف را ساده می‌کنند و حس گشودگی فضا را افزایش می‌دهند.', 'Wall-hung vanities keep the floor clear, simplifying maintenance and opening up compact rooms.'],
        'points' => [
          ['01', 'عایق کامل', 'Sealed core', 'مقاوم در برابر بخار و رطوبت مستقیم.', 'Engineered for high-humidity coastal and mountain climates.'],
          ['02', 'سنگ یکپارچه', 'Monolithic stone', 'روشویی و صفحه با حداقل درز اجرایی.', 'Integrated stone tops with minimal joints.'],
          ['03', 'یراق‌آلات', 'Hardware', 'یراق‌آلات ضدزنگ با ضمانت طولانی‌مدت.', 'Corrosion-resistant hardware backed by warranty.'],
        ],
      ],
      'cafe' => [
        'eye' => ['۰۱ — مجموعه‌ها', '01 — Collections'],
        'title' => ['کافه و رستوران', 'Café & restaurant'],
        'lede' => ['پیشخوان کاری، کنترل صدا، و فضایی که مهمان دوست دارد در آن بماند.', 'Working counters, acoustic calm, and rooms guests want to linger in.'],
        'img' => 'images/col-cafe.jpg',
        'h2' => ['طراحی بر اساس مسیر باریستا و تجربه مهمان', 'Shaped around workflow and guest rhythm'],
        'p1' => ['در پروژه‌های کافه و رستوران، زیبایی بدون سرعت سرویس‌دهی معنا ندارد. ارتفاع، عمق و تأسیسات بار پیش از هر تصمیم دکوراتیو طراحی می‌شود.', 'In hospitality projects, beauty fails without service speed. Bar ergonomics, plumbing and power are resolved before decorative finishes.'],
        'p2' => ['کنترل آکوستیک سقف و دیوارها در کنار نور گرم میزها، گفت‌وگوی آرام مهمانان را ممکن می‌کند.', 'Acoustic timber panelling and low warm table lighting keep conversation effortless even at peak hours.'],
        'points' => [
          ['01', 'سیرکولاسیون', 'Workflow', 'تفکیک مسیر پرسنل از مسیر حرکت مهمان.', 'Clear separation between staff service and guest seating.'],
          ['02', 'دوام تجاری', 'Commercial grade', 'سطوح مقاوم در برابر سایش و شست‌وشوی مداوم.', 'Surfaces built for heavy daily traffic and cleaning.'],
          ['03', 'آکوستیک و نور', 'Sound & light', 'جذب صدا و نورپردازی لایه‌ای قابل تنظیم.', 'Sound absorption paired with dimmable layered light.'],
        ],
      ],
      'studio' => [
        'eye' => ['۰۳ — استودیو', '03 — Studio'],
        'title' => ['استودیوی معماری لایکو', 'Laico Architecture Studio'],
        'lede' => ['طراحی معماری داخلی، ساخت کارگاهی و نصب نهایی در یک تیم واحد.', 'Interior architecture, workshop fabrication and site installation under one roof.'],
        'img' => 'images/about-team.jpg',
        'h2' => ['وقتی طراح و سازنده یکی است', 'When designer and maker are one team'],
        'p1' => ['در بسیاری از پروژه‌ها شکاف میان نقشهٔ معمار و اجرای کارگاهی کیفیت را قربانی می‌کند. در لایکو هر دو تیم در کنار هم کار می‌کنند.', 'In most builds, quality is lost in the gap between architect’s drawings and workshop reality. At Laico, both sit at the same table.'],
        'p2' => ['از برداشت لیزری وضع موجود تا مونتاژ آزمایشی و نصب، مسئولیت تمام جزئیات با یک مجموعه است.', 'From laser site survey to trial workshop assembly and final fitting, one team owns every millimetre.'],
        'points' => [
          ['01', 'برداشت دقیق', 'Laser survey', 'اندازه‌گیری میلی‌متری پیش از شروع ساخت.', 'Millimetre-accurate laser scanning prior to production.'],
          ['02', 'ساخت کارگاهی', 'Workshop build', 'تولید صنعتی دقیق با کنترل کیفیت چندمرحله‌ای.', 'CNC precision combined with hand-finished joinery.'],
          ['03', 'پشتیبانی پایدار', 'Aftercare', '۱۰ سال ضمانت ساخت و ۲ سال خدمات پس از نصب.', '10-year structural warranty and 2-year service support.'],
        ],
      ],
      'systems' => [
        'eye' => ['۰۴ — سیستم‌ها', '04 — Systems'],
        'title' => ['سیستم‌ها و مهندسی ساخت', 'Systems & engineering'],
        'lede' => ['پروفیل‌های آلومینیومی، اتصالات مخفی و تلورانس زیر یک میلی‌متر.', 'Aluminium structural profiles, concealed joinery and sub-millimetre tolerances.'],
        'img' => 'images/detail.jpg',
        'h2' => ['بدون دستگیره، بدون درز اضافی', 'Handleless, without superfluous joints'],
        'p1' => ['سیستم مدولار لایکو امکان یکپارچه‌سازی یخچال، ماشین ظرفشویی، هود و ستون‌های تأسیساتی را پشت سطوح هم‌تراز فراهم می‌کند.', 'Laico’s modular chassis conceals appliances, ventilation and service risers behind flush architectural fronts.'],
        'p2' => ['تمامی ریل‌ها و لولاها از سری‌های رده‌بالای اروپایی انتخاب می‌شوند تا حرکت درها پس از سال‌ها بی‌صدا باقی بماند.', 'Top-tier European runners and hinges ensure silent, balanced motion year after year.'],
        'points' => [
          ['01', 'روکش‌ها', 'Finishes', 'لاک مات چندلایه، چوب طبیعی راش و پرسلان.', 'Multi-coat matte lacquer, natural beech and porcelain slabs.'],
          ['02', 'یراق حرکتی', 'Kinematics', 'ریل فول‌اکستنشن بلوم و لولای آرام‌بند.', 'Blum full-extension runners and soft-close dampers.'],
          ['03', 'ضمانت کتبی', 'Warranty', '۱۰ سال ضمانت بدنه و ساخت، ۲ سال خدمات.', '10-year build guarantee and 2-year dedicated service.'],
        ],
      ],
      'process' => [
        'eye' => ['فرآیند کار', 'Process'],
        'title' => ['از جلسه اول تا تحویل کلید', 'From first meeting to handover'],
        'lede' => ['چهار گام شفاف با زمان‌بندی و برآورد دقیق پیش از ورود به کارگاه.', 'Four transparent stages with fixed schedules and clear budgets before fabrication begins.'],
        'img' => 'images/jr-plan.jpg',
        'h2' => ['تصمیم‌های درست در ترتیب درست', 'Right decisions in the right order'],
        'p1' => ['فرآیند لایکو با شناخت مسیر زندگی یا کار شما آغاز می‌شود؛ سپس پلان عملکردی، نورپردازی و انتخاب متریال در شو روم نهایی می‌گردد.', 'Our process begins with how you live or work, locking down circulation, lighting and tactile samples in the showroom.'],
        'p2' => ['پس از تأیید نقشه‌های کارگاهی (Shop Drawings)، ساخت در کارگاه انجام و پیش از ارسال کنترل تراز می‌شود.', 'Once shop drawings are signed off, units are fabricated and trial-aligned in the workshop before site delivery.'],
        'points' => [
          ['01', 'مشاوره و برداشت', '01 — Brief & survey', 'جلسه شناخت نیازها و برداشت لیزری فضا.', 'Needs discovery and laser site measurement.'],
          ['02', 'طراحی و پالت', '02 — Design & palette', 'ارائه پلان سه‌بعدی و لمس متریال در شو روم.', '3D spatial design and tactile palette sign-off.'],
          ['03', 'ساخت و نصب', '03 — Make & install', 'تولید دقیق کارگاهی و نصب تمیز در محل پروژه.', 'Workshop fabrication and laser-levelled installation.'],
        ],
      ],
      'materials' => [
        'eye' => ['متریال لایکو', 'Materials'],
        'title' => ['متریال صادق و ماندگار', 'Honest, lasting materials'],
        'lede' => ['سنگی که سنگ است، چوبی که چوب است و لاکی که با گذر زمان اصیل می‌ماند.', 'Stone that is stone, timber that is timber, and lacquer that ages with dignity.'],
        'img' => 'images/detail.jpg',
        'h2' => ['پالت محدود، اجرای عمیق', 'Restrained palette, deep craft'],
        'p1' => ['ما در لایکو از متریال‌های تقلیدی استفاده نمی‌کنیم. بافت طبیعی چوب و رگهٔ سنگ بخشی از هویت پروژه است.', 'We avoid faux finishes. Natural timber grain and stone veining are celebrated as core elements of the room.'],
        'p2' => ['در هر فضا حداکثر سه متریال اصلی ترکیب می‌شوند تا چشم آرام بماند و جزئیات ساخت دیده شوند.', 'Each room balances at most three primary materials so the eye rests and joinery proportions stand out.'],
        'points' => [
          ['01', 'سنگ و پرسلان', 'Stone & slab', 'مقاوم در برابر حرارت، خش و لکه.', 'Heat, scratch and stain resistant worktops.'],
          ['02', 'چوب فرآوری‌شده', 'Select timber', 'روکش و چوب طبیعی با پوشش محافظ مات.', 'Natural veneers sealed with low-sheen protective oil/lacquer.'],
          ['03', 'لاک سرمه‌ای و مات', 'Matte lacquer', 'پوشش مخملی ضد اثر انگشت امضای لایکو.', 'Velvet anti-fingerprint matte lacquer in signature Laico tones.'],
        ],
      ],
    ];

    $cfg = $presets[$slug] ?? $presets['kitchen'];

    $heroImgUrl = laico_asset($cfg['img']);
    if ($pageId > 0 && has_post_thumbnail($pageId)) {
      $thumbUrl = get_the_post_thumbnail_url($pageId, 'laico-hero');
      if ($thumbUrl) {
        $heroImgUrl = $thumbUrl;
      }
    }

    $kickerFa = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_kicker_fa', true)) : '') ?: $cfg['eye'][0];
    $kickerEn = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_kicker_en', true)) : '') ?: $cfg['eye'][1];
    $titleFa = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_title_fa', true)) : '') ?: ($pageId > 0 ? get_the_title($pageId) : $cfg['title'][0]);
    $titleEn = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_title_en', true)) : '') ?: $cfg['title'][1];
    $ledeFa = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_lede_fa', true)) : '') ?: $cfg['lede'][0];
    $ledeEn = ($pageId > 0 ? trim((string) get_post_meta($pageId, '_laico_hero_lede_en', true)) : '') ?: $cfg['lede'][1];

    $editorContent = $pageId > 0 ? trim((string) get_post_field('post_content', $pageId)) : '';
  @endphp

  <section class="page-hero">
    <img src="{{ esc_url($heroImgUrl) }}" alt="{{ esc_attr($titleFa) }}" fetchpriority="high">
    <span class="shade"></span>
    <div class="page-hero-copy">
      <p class="eyebrow">{!! laico_bi($kickerFa, $kickerEn) !!}</p>
      <h1>{!! laico_bi($titleFa, $titleEn) !!}</h1>
      <p class="lede">{!! laico_bi($ledeFa, $ledeEn) !!}</p>
    </div>
  </section>

  <section class="wrap prose">
    @if ($editorContent !== '')
      {!! apply_filters('the_content', $editorContent) !!}
    @else
      <div class="split">
        <div class="measure">
          <h2>{!! laico_bi($cfg['h2'][0], $cfg['h2'][1]) !!}</h2>
          <p>{!! laico_bi($cfg['p1'][0], $cfg['p1'][1]) !!}</p>
          <p>{!! laico_bi($cfg['p2'][0], $cfg['p2'][1]) !!}</p>
        </div>
        <div class="media-frame">
          <img src="{{ esc_url($heroImgUrl) }}" alt="{{ esc_attr($titleFa) }}" loading="lazy">
        </div>
      </div>

      <div class="points">
        @foreach ($cfg['points'] as [$num, $pFa, $pEn, $dFa, $dEn])
          <article class="point">
            <b>{{ $num }}</b>
            <h3>{!! laico_bi($pFa, $pEn) !!}</h3>
            <p>{!! laico_bi($dFa, $dEn) !!}</p>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  <section class="cta-band">
    <h2>{!! laico_bi('برای جلسه اول آماده‌ایم.', 'We are ready for the first meeting.') !!}</h2>
    <a class="btn-pill" href="{{ esc_url($navUrls['contact'] ?? home_url('/contact/')) }}" data-i18n="cta">درخواست مشاوره</a>
  </section>
@endsection
