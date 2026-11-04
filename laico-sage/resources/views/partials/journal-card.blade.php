@php
  $isFeatured = !empty($featured);
  $cardClass = $isFeatured ? 'jr-card jr-card--featured' : 'jr-card';
  $headingTag = $isFeatured ? 'h2' : 'h3';
@endphp

<a class="{{ $cardClass }}" href="{{ esc_url($item['url']) }}">
  <span class="jr-media">
    <img src="{{ esc_url($item['image']['url']) }}"
         @if(!empty($item['image']['srcset'])) srcset="{{ esc_attr($item['image']['srcset']) }}" sizes="{{ $isFeatured ? '(max-width: 900px) 100vw, 60vw' : '(max-width: 900px) 100vw, 33vw' }}" @endif
         alt="{{ esc_attr($item['title_fa']) }}"
         data-alt-fa="{{ esc_attr($item['title_fa']) }}"
         data-alt-en="{{ esc_attr($item['title_en']) }}"
         width="{{ (int) $item['image']['width'] }}"
         height="{{ (int) $item['image']['height'] }}"
         @if(!$isFeatured) loading="lazy" decoding="async" @else fetchpriority="high" @endif>
  </span>
  <span class="jr-body">
    <span class="jr-meta">
      <span>{!! laico_bi($item['cat_fa'], $item['cat_en']) !!}</span>
      <time datetime="{{ esc_attr($item['iso_date']) }}">{!! laico_bi($item['date_fa'], $item['date_en']) !!}</time>
      <span>{!! laico_bi($item['read_fa'], $item['read_en']) !!}</span>
    </span>
    <{{ $headingTag }} class="jr-title">{!! laico_bi($item['title_fa'], $item['title_en']) !!}</{{ $headingTag }}>
    <span class="jr-excerpt">{!! laico_bi($item['excerpt_fa'], $item['excerpt_en']) !!}</span>
    <span class="jr-more">{!! laico_bi('ادامهٔ مطلب', 'Read the article') !!}</span>
  </span>
</a>
