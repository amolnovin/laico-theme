{{--
  Template Name: لایکو — صفحه خدمات (Services)
--}}
@extends('layouts.app')

@section('content')
  @php
    $hero = \App\Support\Hero::resolve('services', (int) get_queried_object_id());
    $serviceRows = [
      [
        'num_fa' => '۱', 'num_en' => '01', 'rev' => false,
        'img' => laico_asset('images/services-clinic.jpg'),
        'alt' => 'نمای داخلی مطب دندانپزشکی با یونیت سفید و دیوار شیشه‌ای',
        'title_fa' => 'طراحی مطب و فضای درمانی',
        'title_en' => 'Clinics & healthcare spaces',
        'body_fa' => 'طراحی مطب و فضای درمانی با تمرکز بر آرامش بیماران، رعایت استانداردهای بهداشتی، بهینه‌سازی عملکرد فضا و ایجاد محیطی مدرن و حرفه‌ای.',
        'body_en' => 'Clinics designed around patient calm, hygiene standards, efficient circulation and a modern, professional atmosphere.',
        'href' => $navUrls['projects'] ?? home_url('/projects/'),
      ],
      [
        'num_fa' => '۲', 'num_en' => '02', 'rev' => true,
        'img' => laico_asset('images/services-sport.jpg'),
        'alt' => 'سالن بدنسازی مدرن با نورپردازی خطی',
        'title_fa' => 'طراحی محوطه‌ی ورزشی و باشگاهی',
        'title_en' => 'Sports & gym facilities',
        'body_fa' => 'طراحی محوطه ورزشی و باشگاهی با تمرکز بر عملکرد بهینه، ایمنی، جانمایی اصولی تجهیزات و ایجاد فضایی پویا، مدرن و استاندارد برای فعالیت‌های ورزشی.',
        'body_en' => 'Sports facilities planned for performance, safety and correct equipment zoning — dynamic, modern and built to standard.',
        'href' => $navUrls['projects'] ?? home_url('/projects/'),
      ],
      [
        'num_fa' => '۳', 'num_en' => '03', 'rev' => false,
        'img' => laico_asset('images/services-complex.jpg'),
        'alt' => 'نمای شبانه مجتمع تفریحی با نورپردازی گرم',
        'title_fa' => 'طراحی مجتمع‌های تفریحی',
        'title_en' => 'Leisure & entertainment complexes',
        'body_fa' => 'طراحی مجتمع‌های تفریحی با هدف خلق فضاهایی جذاب، پویا و کاربردی برای سرگرمی، تعامل اجتماعی و تجربه‌ای لذت‌بخش در محیطی هماهنگ با طبیعت و معماری مدرن.',
        'body_en' => 'Leisure complexes that invite social interaction and play, in a setting that balances nature with modern architecture.',
        'href' => $navUrls['projects'] ?? home_url('/projects/'),
      ],
      [
        'num_fa' => '۴', 'num_en' => '04', 'rev' => true,
        'img' => laico_asset('images/services-cafe.jpg'),
        'alt' => 'طراحی داخلی کافه و رستوران',
        'title_fa' => 'طراحی کافه و رستوران',
        'title_en' => 'Cafés & restaurants',
        'body_fa' => 'طراحی کافه و رستوران با تمرکز بر خلق فضایی جذاب و منحصربه‌فرد، چیدمان اصولی، نورپردازی حرفه‌ای و هماهنگی دکوراسیون با هویت برند و تجربه مشتریان.',
        'body_en' => 'Cafés and restaurants shaped around brand identity: considered layouts, professional lighting and a memorable guest experience.',
        'href' => $navUrls['cafe'] ?? home_url('/cafe/'),
      ],
      [
        'num_fa' => '۵', 'num_en' => '05', 'rev' => false,
        'img' => laico_asset('images/services-residential.jpg'),
        'alt' => 'طراحی آشپزخانه و فضای مسکونی',
        'title_fa' => 'طراحی آشپزخانه و فضای مسکونی',
        'title_en' => 'Kitchens & living spaces',
        'body_fa' => 'طراحی آشپزخانه و فضای مسکونی با تمرکز بر زیبایی‌شناسی، بهینه‌سازی فضا، چیدمان کاربردی و ایجاد محیطی هماهنگ، راحت و متناسب با سبک زندگی ساکنان.',
        'body_en' => 'Kitchens and homes tuned to the way people live: honest materials, smart layouts and a calm, coherent space.',
        'href' => $navUrls['kitchen'] ?? home_url('/kitchen/'),
      ],
    ];
  @endphp

  {{-- Dynamic Hero Image from Services Page's Featured Image --}}
  @include('partials.hero-page', ['hero' => $hero])

  <section class="svc-list" id="svc-list" aria-label="حوزه‌های طراحی">
    @foreach ($serviceRows as $svc)
      <article class="svc-row {{ $svc['rev'] ? 'svc-row--rev' : '' }}">
        <div class="svc-media">
          <img src="{{ esc_url($svc['img']) }}" alt="{{ esc_attr($svc['alt']) }}" width="600" height="450" loading="lazy">
        </div>
        <div class="svc-copy">
          <span class="svc-num" aria-hidden="true">{!! laico_bi($svc['num_fa'], $svc['num_en']) !!}</span>
          <h2>{!! laico_bi($svc['title_fa'], $svc['title_en']) !!}</h2>
          <p>{!! laico_bi($svc['body_fa'], $svc['body_en']) !!}</p>
          <a class="svc-btn" href="{{ esc_url($svc['href']) }}">
            {!! laico_bi('مشاهده پروژه‌ها', 'View projects') !!}
            <span class="svc-arrow" aria-hidden="true">
              <svg viewBox="0 0 10 14"><path d="M8 1 2 7 8 13" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>
        </div>
      </article>
    @endforeach

    <nav class="svc-related" aria-label="صفحات مرتبط">
      <span class="svc-related-label">{!! laico_bi('بیشتر درباره لایکو:', 'More about Laico:') !!}</span>
      <a href="{{ esc_url($navUrls['process'] ?? home_url('/process/')) }}">{!! laico_bi('فرآیند کار', 'Process') !!}</a>
      <a href="{{ esc_url($navUrls['systems'] ?? home_url('/systems/')) }}">{!! laico_bi('سیستم‌ها و متریال', 'Systems') !!}</a>
      <a href="{{ esc_url($navUrls['materials'] ?? home_url('/materials/')) }}">{!! laico_bi('متریال', 'Materials') !!}</a>
      <a href="{{ esc_url($navUrls['studio'] ?? home_url('/studio/')) }}">{!! laico_bi('استودیو', 'Studio') !!}</a>
      <a href="{{ esc_url($navUrls['showroom'] ?? home_url('/showroom/')) }}">{!! laico_bi('شو روم', 'Showroom') !!}</a>
      <a href="{{ esc_url($navUrls['privacy'] ?? home_url('/privacy/')) }}">{!! laico_bi('حریم خصوصی', 'Privacy') !!}</a>
      <a href="{{ esc_url($navUrls['cookies'] ?? home_url('/cookies/')) }}">{!! laico_bi('کوکی‌ها', 'Cookies') !!}</a>
    </nav>
  </section>

  <section class="svc-why" aria-labelledby="svc-why-title">
    <div class="svc-why-inner">
      <div class="svc-why-copy">
        <h2 id="svc-why-title">{!! laico_bi('چرا لایکودیزاین؟', 'Why Laico Design?') !!}</h2>
        <p>{!! laico_bi('ترکیبی از خلاقیت، زیبایی‌شناسی و عملکرد در کنار توجه به جزییات، شناخت نیازهای کارفرما و ارائه راهکارهای معماری منحصربه‌فرد که هر فضا را به تجربه‌ای ماندگار و کاربردی تبدیل می‌کند.', 'Creativity, aesthetics and function, paired with attention to detail and a real understanding of the client — architectural solutions that turn every space into a lasting, usable experience.') !!}</p>
      </div>
      <div class="svc-why-media">
        <img src="{{ esc_url(laico_asset('images/services-why.jpg')) }}" alt="نشیمن سرمه‌ای لایکو دیزاین" width="369" height="322" loading="lazy">
      </div>
    </div>
  </section>

  @include('partials.svc-consult', ['sourcePage' => 'خدمات'])
@endsection
