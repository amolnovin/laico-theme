{{--
  Template Name: لایکو — شو روم (Showroom)
--}}
@extends('layouts.app')

@section('content')
  @php
    $phoneDisplay = (string) ($themeOpt['phone_primary'] ?? '۰۹۱۲۰۸۹۸۹۳۸');
    $phoneTel = preg_replace('/[^\d+]/', '', (string) ($themeOpt['phone_primary_tel'] ?? '+989120898938')) ?: '+989120898938';
  @endphp

  <section class="showroom" style="min-height:70vh;display:flex;align-items:flex-end">
    <div class="showroom-inner">
      <p class="eyebrow" data-i18n="sh-eye">۰۵ — شو روم</p>
      <h1 data-i18n="sh-title">متریال و کیفیت را لمس کنید</h1>
      <p class="body" data-i18n="sh-body">بازدید از شو روم با هماهنگی قبلی؛ همراه با مشاوره فنی و انتخاب پالت متریال.</p>
      <dl class="info-list">
        <div class="info-row">
          <dt data-i18n="addr">آدرس</dt>
          <dd>{!! laico_bi((string) ($themeOpt['address_fa'] ?? 'آمل، دریای ۲۰'), (string) ($themeOpt['address_en'] ?? 'Amol, Darya 20')) !!}</dd>
        </div>
        <div class="info-row">
          <dt data-i18n="hours">ساعات</dt>
          <dd>{!! laico_bi((string) ($themeOpt['hours_fa'] ?? 'شنبه تا پنجشنبه، ۱۰ تا ۱۹'), (string) ($themeOpt['hours_en'] ?? 'Sat – Thu, 10:00 – 19:00')) !!}</dd>
        </div>
        <div class="info-row">
          <dt data-i18n="phone">تماس</dt>
          <dd class="num"><a href="tel:{{ esc_attr($phoneTel) }}" dir="ltr">{{ $phoneDisplay }}</a></dd>
        </div>
      </dl>
    </div>
  </section>

  <section class="wrap prose">
    <div class="split">
      <div class="measure">
        <h2>{!! laico_bi('در شو روم چه می‌بینید', 'What you will see') !!}</h2>
        <p>{!! laico_bi('نمونه‌های روکش لاک مات، چوب راش، سنگ، و یک قطعه از سیستم بدون دستگیره. پالت را این‌جا انتخاب می‌کنیم، نه از روی عکس.', 'Samples of matte lacquer, beech, stone, and a piece of the handleless system. The palette is chosen here, not from a photograph.') !!}</p>
        <p>{!! laico_bi('بازدید فقط با وقت قبلی است تا مشاور فنی همان روز کنار شما باشد.', 'Visits are by appointment so a technical consultant is with you that day.') !!}</p>
      </div>
      <div class="media-frame">
        <img src="{{ esc_url(laico_asset('images/detail.jpg')) }}" alt="جزئیات متریال در شو روم لایکو" loading="lazy">
      </div>
    </div>
  </section>

  @include('partials.home-collaborate', ['sourcePage' => 'شو روم', 'defaultType' => 'kitchen'])
@endsection
