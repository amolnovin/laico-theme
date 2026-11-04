{{--
  Dynamic Page Hero Section (`partials/hero-page.blade.php`)
  Pulls image + srcset + alt from the WordPress Page's Featured Image (`Hero::resolve()`).
--}}
@php
  $heroClass = 'hero';
  if (!empty($hero['is_page_variant'])) {
    $heroClass .= ' hero--page';
  }
  if (!empty($extraHeroClass)) {
    $heroClass .= ' ' . $extraHeroClass;
  }
@endphp

<section class="{{ $heroClass }}" id="top">
  <div class="hero-media">
    <img src="{{ esc_url($hero['url']) }}"
         @if(!empty($hero['srcset'])) srcset="{{ esc_attr($hero['srcset']) }}" sizes="{{ esc_attr($hero['sizes'] ?? '100vw') }}" @endif
         alt="{{ esc_attr($hero['alt'] ?? '') }}"
         width="{{ (int) ($hero['width'] ?? 1376) }}"
         height="{{ (int) ($hero['height'] ?? 768) }}"
         fetchpriority="high"
         decoding="async">
  </div>
  <div class="hero-shade"></div>
  <div class="hero-copy">
    @if(!empty($hero['kicker_fa']))
      <p class="hero-kicker">{!! laico_bi($hero['kicker_fa'], $hero['kicker_en'] ?? $hero['kicker_fa']) !!}</p>
    @endif
    <h1>{!! laico_bi($hero['title_fa'], $hero['title_en'] ?? $hero['title_fa']) !!}</h1>
    <div class="hero-rule"></div>
    @if(!empty($hero['lede_fa']))
      <p class="lede">{!! laico_bi($hero['lede_fa'], $hero['lede_en'] ?? $hero['lede_fa']) !!}</p>
    @endif
  </div>

  {!! $slot ?? '' !!}

  @if(!empty($hero['hint']) && !empty($hero['target']))
    <a class="scroll-hint" href="#{{ esc_attr($hero['target']) }}" dir="ltr" aria-label="Scroll">
      <span>SCROLL</span><i></i>
    </a>
  @endif
</section>
