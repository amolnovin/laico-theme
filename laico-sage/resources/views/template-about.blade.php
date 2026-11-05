{{--
  Template Name: لایکو — درباره ما (About)
--}}
@extends('layouts.app')

@section('content')
  @php
    $pageId = (int) get_queried_object_id();
    if ($pageId <= 0) {
      $pageId = \App\Support\Hero::findSectionPageId('about');
    }
    $hero = \App\Support\Hero::resolve('about', $pageId);
    $about = \App\PostTypes\PageAndPostMeta::resolveAboutPageData($pageId);
    $rawContent = $pageId > 0 ? trim((string) get_post_field('post_content', $pageId)) : '';
  @endphp

  {{-- Dynamic Hero Image from About Page's Featured Image --}}
  @include('partials.hero-page', ['hero' => $hero])

  <section class="about-story" id="about-story">
    @if (!empty($about['show_lead']))
      <p class="about-lead">
        {!! laico_bi($about['lead_fa'], $about['lead_en'], true) !!}
      </p>
    @endif

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

  @if (!empty($about['show_collage']))
    <section class="about-collage" aria-label="نمونه اجرا">
      <div class="about-collage-stage">
        @foreach ($about['collage'] as $item)
          @if (!empty($item['show']))
            <img class="{{ esc_attr($item['class']) }}"
                 src="{{ esc_url($item['url']) }}"
                 @if (!empty($item['srcset'])) srcset="{{ esc_attr($item['srcset']) }}" sizes="(max-width: 720px) 50vw, 45vw" @endif
                 alt="{{ esc_attr($item['alt']) }}"
                 width="{{ (int) $item['width'] }}"
                 height="{{ (int) $item['height'] }}"
                 loading="lazy"
                 decoding="async">
          @endif
        @endforeach
      </div>
    </section>
  @endif

  @if (!empty($about['show_team']))
    <section class="about-team">
      <div class="about-team-inner">
        <h2>{!! laico_bi($about['team_title_fa'], $about['team_title_en']) !!}</h2>

        @if (!empty($about['show_team_image']) && !empty($about['team_image']['url']))
          <div class="about-team-media" style="margin-bottom:32px;border-radius:var(--radius-lg, 20px);overflow:hidden;">
            <img src="{{ esc_url($about['team_image']['url']) }}"
                 @if (!empty($about['team_image']['srcset'])) srcset="{{ esc_attr($about['team_image']['srcset']) }}" sizes="(max-width: 1180px) 100vw, 1120px" @endif
                 alt="{{ esc_attr($about['team_image']['alt']) }}"
                 width="{{ (int) $about['team_image']['width'] }}"
                 height="{{ (int) $about['team_image']['height'] }}"
                 style="width:100%;max-height:560px;object-fit:cover;display:block;"
                 loading="lazy"
                 decoding="async">
          </div>
        @endif

        @if ($rawContent !== '')
          <div class="prose" style="padding:0;margin-bottom:32px;">{!! apply_filters('the_content', $rawContent) !!}</div>
        @endif
      </div>
    </section>
  @endif

  @include('partials.about-consult', ['sourcePage' => 'درباره ما', 'panelAtEnd' => false])
@endsection
