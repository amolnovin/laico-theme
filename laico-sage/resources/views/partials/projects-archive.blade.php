@php
  $categories = !empty($filterTerms['categories']) ? $filterTerms['categories'] : [
    ['slug' => 'residential', 'fa' => 'مسکونی', 'en' => 'Residential'],
    ['slug' => 'commercial', 'fa' => 'تجاری', 'en' => 'Commercial'],
  ];

  $spaces = !empty($filterTerms['spaces']) ? $filterTerms['spaces'] : [
    ['slug' => 'kitchen', 'fa' => 'آشپزخانه', 'en' => 'Kitchen'],
    ['slug' => 'living', 'fa' => 'نشیمن و خواب', 'en' => 'Living & bedroom'],
    ['slug' => 'bath', 'fa' => 'حمام', 'en' => 'Bath'],
    ['slug' => 'office', 'fa' => 'اداری و درمانی', 'en' => 'Office & clinic'],
    ['slug' => 'hall', 'fa' => 'کافه و رستوران', 'en' => 'Café & restaurant'],
  ];

  $cities = !empty($filterTerms['cities']) ? $filterTerms['cities'] : [
    ['slug' => 'amol', 'fa' => 'آمل', 'en' => 'Amol'],
    ['slug' => 'tehran', 'fa' => 'تهران', 'en' => 'Tehran'],
    ['slug' => 'mazandaran', 'fa' => 'مازندران', 'en' => 'Mazandaran'],
  ];

  $years = $filterTerms['years'] ?? [];
  $initCat = $initialFilters['category'] ?? 'all';
  $initSpace = $initialFilters['space'] ?? 'all';
  $initCity = $initialFilters['city'] ?? 'all';
@endphp

{{-- Dynamic Hero Image from the `projects` Page's Featured Image --}}
@include('partials.hero-page', ['hero' => $hero])

<section class="prj-filters" id="prj-list" aria-label="فیلتر پروژه‌ها">
  <div class="prj-filters-inner">
    <div class="prj-filter-top">
      <div class="prj-tabs" role="group" aria-label="دسته‌بندی">
        <button type="button" class="prj-tab {{ $initCat === 'all' ? 'is-active' : '' }}" data-category="all">{!! laico_bi('همه پروژه‌ها', 'All projects') !!}</button>
        @foreach ($categories as $cat)
          <button type="button" class="prj-tab {{ $initCat === $cat['slug'] ? 'is-active' : '' }}" data-category="{{ esc_attr($cat['slug']) }}">{!! laico_bi($cat['fa'], $cat['en']) !!}</button>
        @endforeach
      </div>
      <div class="prj-search">
        <label class="visually-hidden" for="prj-search-input">{!! laico_bi('جستجوی پروژه', 'Search projects') !!}</label>
        <input id="prj-search-input" type="search" data-prj-search data-ph-fa="جستجوی پروژه…" data-ph-en="Search projects…" placeholder="جستجوی پروژه…" autocomplete="off">
      </div>
    </div>

    <div class="prj-spaces" role="group" aria-label="نوع فضا">
      <button type="button" class="prj-pill {{ $initSpace === 'all' ? 'is-active' : '' }}" data-space-filter="all">{!! laico_bi('همه فضاها', 'All spaces') !!}</button>
      @foreach ($spaces as $sp)
        <button type="button" class="prj-pill {{ $initSpace === $sp['slug'] ? 'is-active' : '' }}" data-space-filter="{{ esc_attr($sp['slug']) }}">{!! laico_bi($sp['fa'], $sp['en']) !!}</button>
      @endforeach
    </div>

    <div class="prj-selects">
      <label class="prj-select-group">
        <span class="prj-select-label">{!! laico_bi('موقعیت', 'Location') !!}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="city">
            <option value="all" data-fa="همه شهرها" data-en="All cities" @if($initCity === 'all') selected @endif>همه شهرها</option>
            @foreach ($cities as $c)
              <option value="{{ esc_attr($c['slug']) }}" data-fa="{{ esc_attr($c['fa']) }}" data-en="{{ esc_attr($c['en']) }}" @if($initCity === $c['slug']) selected @endif>{{ $c['fa'] }}</option>
            @endforeach
          </select>
        </span>
      </label>

      <label class="prj-select-group">
        <span class="prj-select-label">{!! laico_bi('سال', 'Year') !!}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="year">
            <option value="all" data-fa="همه سال‌ها" data-en="All years">همه سال‌ها</option>
            @foreach ($years as $yr)
              <option value="{{ esc_attr($yr['value']) }}" data-fa="{{ esc_attr($yr['fa']) }}" data-en="{{ esc_attr($yr['en']) }}">{{ $yr['fa'] }}</option>
            @endforeach
          </select>
        </span>
      </label>

      <label class="prj-select-group">
        <span class="prj-select-label">{!! laico_bi('متراژ', 'Area') !!}</span>
        <span class="prj-select-wrap">
          <select data-prj-select="size">
            <option value="all" data-fa="همه ابعاد" data-en="All sizes">همه ابعاد</option>
            <option value="small" data-fa="کوچک (زیر ۱۲۰ متر)" data-en="Small (under 120 m²)">کوچک (زیر ۱۲۰ متر)</option>
            <option value="medium" data-fa="متوسط (۱۲۰ تا ۳۰۰ متر)" data-en="Medium (120–300 m²)">متوسط (۱۲۰ تا ۳۰۰ متر)</option>
            <option value="large" data-fa="بزرگ (بالای ۳۰۰ متر)" data-en="Large (over 300 m²)">بزرگ (بالای ۳۰۰ متر)</option>
          </select>
        </span>
      </label>
    </div>
  </div>
</section>

<div class="prj-results">
  <h2>{!! laico_bi('نتایج جستجو', 'Results') !!}</h2>
  <span class="prj-count" data-prj-count></span>
</div>

<section class="prj-grid-wrap" aria-label="پروژه‌ها">
  <div class="prj-grid"
       data-prj-grid
       data-per-page="9"
       data-init-category="{{ esc_attr($initCat) }}"
       data-init-space="{{ esc_attr($initSpace) }}">
    @foreach ($projectsList as $proj)
      @include('partials.project-card', ['proj' => $proj])
    @endforeach
  </div>
  <div class="prj-empty" data-prj-empty @if(!empty($projectsList)) hidden @endif>
    {!! laico_bi('پروژه‌ای با این فیلترها یافت نشد.', 'No projects match these filters.') !!}
  </div>
  <nav class="pager" data-prj-pager aria-label="Pagination" hidden></nav>
</section>

@include('partials.svc-consult', ['sourcePage' => 'آرشیو پروژه‌ها'])
