<!DOCTYPE html>
<html {!! ($themeOpt['default_language'] ?? 'fa') === 'en' ? 'lang="en" dir="ltr"' : 'lang="fa" dir="rtl"' !!}>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0b1d33">
  @php($fav = !empty($themeOpt['favicon_url']) ? $themeOpt['favicon_url'] : laico_asset('images/favicon.svg'))
  <link rel="icon" href="{{ esc_url($fav) }}" type="{{ str_ends_with($fav, '.svg') ? 'image/svg+xml' : 'image/png' }}">
  @php(do_action('get_header'))
  @php(wp_head())
</head>
<body @php(body_class())
  data-page="{{ esc_attr($currentSection ?? 'home') }}"
  data-title-fa="{{ esc_attr($pageTitles['fa'] ?? $siteName) }}"
  data-title-en="{{ esc_attr($pageTitles['en'] ?? 'Laico Design') }}">
  @php(wp_body_open())

  @include('sections.header')

  <main id="main">
    @yield('content')
  </main>

  @include('sections.footer')

  @php(do_action('get_footer'))
  @php(wp_footer())
</body>
</html>
