@php
  $ctaHref = ($currentSection ?? '') === 'home' ? '#collaborate' : ($navUrls['contact'] ?? home_url('/contact/'));
  $navItems = [
    ['key' => 'home', 'i18n' => 'nav-home', 'label' => 'صفحه اصلی', 'url' => $navUrls['home'] ?? home_url('/')],
    ['key' => 'services', 'i18n' => 'nav-services', 'label' => 'خدمات', 'url' => $navUrls['services'] ?? home_url('/services/')],
    ['key' => 'projects', 'i18n' => 'nav-projects', 'label' => 'پروژه‌ها', 'url' => $navUrls['projects'] ?? home_url('/projects/')],
    ['key' => 'journal', 'i18n' => 'nav-journal', 'label' => 'مجله', 'url' => $navUrls['journal'] ?? home_url('/journal/')],
    ['key' => 'about', 'i18n' => 'nav-about', 'label' => 'درباره ما', 'url' => $navUrls['about'] ?? home_url('/about/')],
    ['key' => 'contact', 'i18n' => 'nav-contact', 'label' => 'تماس با ما', 'url' => $navUrls['contact'] ?? home_url('/contact/')],
  ];
@endphp

<header class="site-header" data-header>
  <div class="header-bar">
    <div class="header-start">
      <a class="logo" href="{{ esc_url($navUrls['home'] ?? home_url('/')) }}" aria-label="Laico Design">
        <img src="{{ esc_url($logoUrl) }}" alt="{{ esc_attr($siteName) }}" width="357" height="232">
      </a>
      <nav class="desk-nav" aria-label="main">
        @foreach ($navItems as $item)
          <a href="{{ esc_url($item['url']) }}"
             data-nav="{{ esc_attr($item['key']) }}"
             data-i18n="{{ esc_attr($item['i18n']) }}"
             @if(($currentSection ?? '') === $item['key']) class="is-current" aria-current="page" @endif>{{ $item['label'] }}</a>
        @endforeach
      </nav>
    </div>

    <div class="header-tools">
      @if(!empty($themeOpt['enable_lang_switcher']))
        <div class="lang" role="group" aria-label="Language">
          <button type="button" data-lang="fa" class="{{ ($themeOpt['default_language'] ?? 'fa') === 'fa' ? 'is-active' : '' }}">FA</button>
          <span aria-hidden="true">/</span>
          <button type="button" data-lang="en" class="{{ ($themeOpt['default_language'] ?? 'fa') === 'en' ? 'is-active' : '' }}">EN</button>
        </div>
      @endif

      <a class="btn-pill header-cta" href="{{ esc_url($ctaHref) }}" data-i18n="cta">درخواست مشاوره</a>

      <button class="burger" type="button" data-burger aria-expanded="false" aria-controls="nav-panel" data-i18n-aria="menu" aria-label="منو">
        <span></span><span></span>
      </button>
    </div>
  </div>

  <div class="nav-panel" id="nav-panel">
    @foreach ($navItems as $item)
      <a href="{{ esc_url($item['url']) }}"
         data-nav="{{ esc_attr($item['key']) }}"
         data-i18n="{{ esc_attr($item['i18n']) }}"
         @if(($currentSection ?? '') === $item['key']) class="is-current" aria-current="page" @endif>{{ $item['label'] }}</a>
    @endforeach
    <a class="btn-pill" href="{{ esc_url($ctaHref) }}" data-i18n="cta">درخواست مشاوره</a>
  </div>
</header>
