@extends('layouts.app')

@section('content')
  <section class="pd-top ar-top" style="min-height:65vh;display:flex;align-items:center;">
    <div class="ar-wrap" style="text-align:center;">
      <p class="eyebrow" style="color:var(--gold);">404</p>
      <h1>{!! laico_bi('صفحهٔ مورد نظر یافت نشد', 'Page not found') !!}</h1>
      <p class="lede" style="margin:16px auto 32px;max-width:520px;color:var(--muted);">
        {!! laico_bi('ممکن است آدرس صفحه تغییر کرده باشد یا به بخش دیگری منتقل شده باشد.', 'The page you are looking for may have moved or no longer exists.') !!}
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a class="btn-solid" href="{{ esc_url($navUrls['home'] ?? home_url('/')) }}">
          {!! laico_bi('بازگشت به صفحه اصلی', 'Back to home') !!}
        </a>
        <a class="btn-line" href="{{ esc_url($navUrls['projects'] ?? home_url('/projects/')) }}">
          {!! laico_bi('مشاهده پروژه‌ها', 'Explore projects') !!}
        </a>
      </div>
    </div>
  </section>
@endsection
