{{--
  Template Name: لایکو — درباره ما (About)
--}}
@extends('layouts.app')

@section('content')
  @php
    $pageId = (int) get_queried_object_id();
    $hero = \App\Support\Hero::resolve('about', $pageId);
    $rawContent = $pageId > 0 ? trim((string) get_post_field('post_content', $pageId)) : '';
  @endphp

  {{-- Dynamic Hero Image from About Page's Featured Image --}}
  @include('partials.hero-page', ['hero' => $hero])

  <section class="about-story" id="about-story">
    <p class="about-lead">
      <span class="lang-fa">معماری برای ما، گفتگو میان نور، ماده و زندگی است. ما در<br class="about-br"><strong>لایکودیزاین</strong> معتقدیم هر کنج از یک فضا، پتانسیل تبدیل شدن به<br class="about-br">اثری ماندگار را دارد. از نخستین خطوط روی کاغذ تا آخرین جزئیات<br class="about-br">اجرا، تلاش می‌کنیم مرزهای میان زیبایی‌شناسی مینیمال و<br class="about-br">کارآمدی مدرن را کمرنگ کنیم.</span>
      <span class="lang-en" hidden>For us, architecture is a conversation between light, material and life. At <strong>Laico Design</strong> we believe every corner of a space can become a lasting work. From the first lines on paper to the last detail of the build, we soften the line between minimal beauty and modern usefulness.</span>
    </p>

    <ul class="about-values">
      <li>
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <path d="M32 7.5l2.1 12.6 12.4 2.4-12.4 3.1L32 38.5l-2.1-13L17.5 22.5l12.4-2.4L32 7.5z"/>
          <path d="M47.5 12.2l.8 3.1 3.1.8-3.1.8-.8 3.1-.8-3.1-3.1-.8 3.1-.8.8-3.1z"/>
          <path d="M15.2 13.4l.6 2.3 2.3.6-2.3.6-.6 2.3-.6-2.3-2.3-.6 2.3-.6.6-2.3z"/>
          <path d="M49.2 38.2l.6 2.4 2.4.6-2.4.6-.6 2.4-.6-2.4-2.4-.6 2.4-.6.6-2.4z"/>
          <path d="M16.8 41.2l.5 1.9 1.9.5-1.9.5-.5 1.9-.5-1.9-1.9-.5 1.9-.5.5-1.9z"/>
        </svg>
        <span>{!! laico_bi('زیبایی عملکردگرا', 'Functional beauty') !!}</span>
      </li>
      <li>
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <path d="M34 14c7.2-4.2 16.4-5.2 22-3.2-1.6 8.4-6.6 18.6-13.6 24.2-5.4 4.2-12.2 6-16.2 2.6 1.4-7.2 3.2-15.2 7.8-23.6z"/>
          <path d="M32 38c3.6-5.6 9.2-11.2 15.4-15.2"/>
          <path d="M8 24h13"/>
          <path d="M6 33h15"/>
        </svg>
        <span>{!! laico_bi('کیفیت بالا', 'High quality') !!}</span>
      </li>
      <li>
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <g transform="rotate(-40 32 32)">
            <rect x="8" y="23" width="48" height="16" rx="1.2"/>
            <path d="M16 23v5.2M22 23v3.4M28 23v5.2M34 23v3.4M40 23v5.2M46 23v3.4"/>
          </g>
        </svg>
        <span>{!! laico_bi('طراحی هوشمند', 'Smart design') !!}</span>
      </li>
      <li>
        <svg viewBox="0 0 64 64" aria-hidden="true">
          <circle cx="32" cy="32" r="16"/>
          <path d="M32 20.5V32l8 6"/>
        </svg>
        <span>{!! laico_bi('تحویل به موقع', 'On-time delivery') !!}</span>
      </li>
    </ul>
  </section>

  <section class="about-collage" aria-label="نمونه اجرا">
    <div class="about-collage-stage">
      <img class="c-sink" src="{{ esc_url(laico_asset('images/about-k-sink.jpg')) }}" alt="سینک و کابینت سرمه‌ای" width="476" height="336" loading="lazy">
      <img class="c-run" src="{{ esc_url(laico_asset('images/about-k-run.jpg')) }}" alt="کابینت سرمه‌ای با صفحه سنگ" width="748" height="394" loading="lazy">
      <img class="c-base" src="{{ esc_url(laico_asset('images/about-k-base.jpg')) }}" alt="جزئیات کشوها و دستگیره‌ها" width="654" height="548" loading="lazy">
      <img class="c-glass" src="{{ esc_url(laico_asset('images/about-k-glass.jpg')) }}" alt="کابینت شیشه‌ای دیواری" width="510" height="514" loading="lazy">
    </div>
  </section>

  <section class="about-team">
    <div class="about-team-inner">
      <h2>{!! laico_bi('گروه معماران و طراحان', 'Architects and designers') !!}</h2>
      @if ($rawContent !== '')
        <div class="prose" style="padding:0;margin-bottom:32px;">{!! apply_filters('the_content', $rawContent) !!}</div>
      @else
        <div style="margin-bottom:32px;border-radius:8px;overflow:hidden;">
          <img src="{{ esc_url(laico_asset('images/about-team.jpg')) }}" alt="استودیوی معماری و طراحی لایکو" style="width:100%;max-height:520px;object-fit:cover;display:block;" loading="lazy">
        </div>
      @endif
    </div>
  </section>

  @include('partials.about-consult', ['sourcePage' => 'درباره ما', 'panelAtEnd' => false])
@endsection
