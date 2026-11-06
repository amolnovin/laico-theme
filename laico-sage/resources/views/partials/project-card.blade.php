@php
  $searchTokens = mb_strtolower(implode(' ', array_filter([
    $proj['title_fa'] ?? '',
    $proj['title_en'] ?? '',
    $proj['city_fa'] ?? '',
    $proj['city_en'] ?? '',
    $proj['space_fa'] ?? '',
    $proj['space_en'] ?? '',
    $proj['place_fa'] ?? '',
  ])));
@endphp

<a class="prj-card"
   data-prj-card
   data-category="{{ esc_attr($proj['category_slug']) }}"
   data-categories="{{ esc_attr($proj['category_slugs'] ?? $proj['category_slug']) }}"
   data-space="{{ esc_attr($proj['space_slug']) }}"
   data-spaces="{{ esc_attr($proj['space_slugs'] ?? $proj['space_slug']) }}"
   data-city="{{ esc_attr($proj['city_slug']) }}"
   data-year="{{ esc_attr($proj['year']) }}"
   data-size="{{ esc_attr($proj['size_bucket']) }}"
   data-q="{{ esc_attr($searchTokens) }}"
   href="{{ esc_url($proj['url']) }}">
  <img src="{{ esc_url($proj['image']['url']) }}"
       alt="{{ esc_attr($proj['title_fa'] . ' — ' . $proj['space_fa']) }}"
       data-alt-fa="{{ esc_attr($proj['title_fa'] . ' — ' . $proj['space_fa']) }}"
       data-alt-en="{{ esc_attr($proj['title_en'] . ' — ' . $proj['space_en']) }}"
       width="{{ (int) $proj['image']['width'] }}"
       height="{{ (int) $proj['image']['height'] }}"
       loading="lazy"
       decoding="async">
  <span class="prj-shade" aria-hidden="true"></span>
  <span class="prj-tag">{!! laico_bi($proj['space_fa'], $proj['space_en']) !!}</span>
  <span class="prj-view">{!! laico_bi('مشاهده پروژه', 'View project') !!}</span>
  <span class="prj-title">{!! laico_bi($proj['title_fa'], $proj['title_en']) !!}</span>
</a>
