<!DOCTYPE html>
@php($isEnglishActive = \App\Services\ThemeOptions::isEnglishEnabled() && ($themeOpt['default_language'] ?? 'fa') === 'en')
<html {!! $isEnglishActive ? 'lang="en" dir="ltr"' : 'lang="fa" dir="rtl"' !!}>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b1d33">
  @php($fav = !empty($themeOpt['favicon_url']) ? $themeOpt['favicon_url'] : laico_asset('images/favicon.svg'))
  <link rel="icon" href="{{ esc_url($fav) }}" type="{{ str_ends_with($fav, '.svg') ? 'image/svg+xml' : (str_ends_with($fav, '.webp') ? 'image/webp' : 'image/png') }}">
  @php(do_action('get_header'))
  @php(wp_head())
</head>
<body @php(body_class())
  data-page="{{ esc_attr($currentSection ?? 'home') }}"
  data-title-fa="{{ esc_attr($pageTitles['fa'] ?? $siteName) }}"
  data-title-en="{{ esc_attr($pageTitles['en'] ?? 'Laico Design') }}">
  @php(wp_body_open())

  @if (\App\Services\ThemeOptions::get('opt_page_loader', true))
    <div id="laico-page-loader" class="laico-page-loader" aria-hidden="true" role="status">
      <div class="laico-page-loader__box">
        <span class="laico-page-loader__spinner" aria-hidden="true"></span>
        <span class="laico-page-loader__label">{!! laico_bi('در حال بارگذاری…', 'Loading…') !!}</span>
      </div>
    </div>
  @endif

  @include('sections.header')

  <main id="main">
    @yield('content')
  </main>

  @include('sections.footer')

  @php(do_action('get_footer'))
  @php(wp_footer())
</body>
</html>
