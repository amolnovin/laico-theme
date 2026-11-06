{{--
  Template Name: لایکو — صفحه خدمات (Services)
--}}
@extends('layouts.app')

@section('content')
  @php
    $pageId = (int) get_queried_object_id();
    if ($pageId <= 0) {
      $pageId = \App\Support\Hero::findSectionPageId('services');
    }
    $hero = \App\Support\Hero::resolve('services', $pageId);
    $svcPage = \App\PostTypes\PageAndPostMeta::resolveServicesPageData($pageId);
  @endphp

  {{-- Dynamic Hero Image from Services Page's Featured Image --}}
  @include('partials.hero-page', ['hero' => $hero])

  @if (!empty($svcPage['show_list']) && !empty($svcPage['rows']))
    <section class="svc-list" id="svc-list" aria-label="حوزه‌های طراحی">
      @foreach ($svcPage['rows'] as $svc)
        @php
          $idxOne = $loop->iteration;
          $numFa = \App\Support\Jalali::toPersianDigits((string) $idxOne);
          $numEn = str_pad((string) $idxOne, 2, '0', STR_PAD_LEFT);
          $img = $svc['image'];
        @endphp
        <article class="svc-row {{ $loop->even ? 'svc-row--rev' : '' }}">
          <div class="svc-media">
            <img src="{{ esc_url($img['url']) }}"
                 @if (!empty($img['srcset'])) srcset="{{ esc_attr($img['srcset']) }}" sizes="(max-width: 900px) 100vw, 50vw" @endif
                 alt="{{ esc_attr($img['alt']) }}"
                 width="{{ (int) ($img['width'] ?: 600) }}"
                 height="{{ (int) ($img['height'] ?: 450) }}"
                 loading="lazy"
                 decoding="async">
          </div>
          <div class="svc-copy">
            <span class="svc-num" aria-hidden="true">{!! laico_bi($numFa, $numEn) !!}</span>
            <h2>{!! laico_bi($svc['title_fa'], $svc['title_en']) !!}</h2>
            @if (!empty($svc['body_fa']))
              <p>{!! laico_bi($svc['body_fa'], $svc['body_en']) !!}</p>
            @endif
            <a class="svc-btn" href="{{ esc_url($svc['btn_url']) }}" @if(!empty($svc['space_slug'])) data-target-space="{{ esc_attr($svc['space_slug']) }}" @endif>
              {!! laico_bi($svc['btn_fa'], $svc['btn_en']) !!}
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
      </nav>
    </section>
  @endif

  @if (!empty($svcPage['show_why']))
    <section class="svc-why" aria-labelledby="svc-why-title">
      <div class="svc-why-inner">
        <div class="svc-why-copy">
          <h2 id="svc-why-title">{!! laico_bi($svcPage['why_title_fa'], $svcPage['why_title_en']) !!}</h2>
          @if (!empty($svcPage['why_body_1_fa']))
            <p>{!! laico_bi($svcPage['why_body_1_fa'], $svcPage['why_body_1_en']) !!}</p>
          @endif
          @if (!empty($svcPage['why_body_2_fa']))
            <p>{!! laico_bi($svcPage['why_body_2_fa'], $svcPage['why_body_2_en']) !!}</p>
          @endif
        </div>
        @if (!empty($svcPage['show_why_image']) && !empty($svcPage['why_image']['url']))
          <div class="svc-why-media">
            <img src="{{ esc_url($svcPage['why_image']['url']) }}"
                 @if (!empty($svcPage['why_image']['srcset'])) srcset="{{ esc_attr($svcPage['why_image']['srcset']) }}" sizes="(max-width: 900px) 100vw, 400px" @endif
                 alt="{{ esc_attr($svcPage['why_image']['alt']) }}"
                 width="{{ (int) ($svcPage['why_image']['width'] ?: 369) }}"
                 height="{{ (int) ($svcPage['why_image']['height'] ?: 322) }}"
                 loading="lazy"
                 decoding="async">
          </div>
        @endif
      </div>
    </section>
  @endif

  @include('partials.svc-consult', ['sourcePage' => 'خدمات'])
@endsection
