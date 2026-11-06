@extends('layouts.app')

@section('content')
  <section class="pd-top ar-top">
    <div class="ar-wrap">
      <nav class="crumbs" aria-label="breadcrumb">
        <a href="{{ esc_url($navUrls['journal'] ?? home_url('/journal/')) }}">{!! laico_bi('مجله', 'Journal') !!}</a>
        <span aria-hidden="true">/</span>
        <span>{!! laico_bi($article['cat_fa'], $article['cat_en']) !!}</span>
      </nav>
      <h1>{!! laico_bi($article['title_fa'], $article['title_en']) !!}</h1>
      <p class="ar-meta">
        <span>{!! laico_bi($article['cat_fa'], $article['cat_en']) !!}</span>
        <time datetime="{{ esc_attr($article['iso_date']) }}">{!! laico_bi($article['date_fa'], $article['date_en']) !!}</time>
        <span>{!! laico_bi($article['read_fa'], $article['read_en']) !!}</span>
      </p>
    </div>
  </section>

  <article class="ar">
    <figure class="ar-cover">
      <img src="{{ esc_url($article['image']['url']) }}"
           alt="{{ esc_attr($article['title_fa']) }}"
           data-alt-fa="{{ esc_attr($article['title_fa']) }}"
           data-alt-en="{{ esc_attr($article['title_en']) }}"
           width="{{ (int) $article['image']['width'] }}"
           height="{{ (int) $article['image']['height'] }}"
           fetchpriority="high">
    </figure>

    <div class="ar-body">
      @if(!empty($article['excerpt_fa']))
        <p class="ar-lead">{!! laico_bi($article['excerpt_fa'], $article['excerpt_en']) !!}</p>
      @endif

      @if(!empty($article['has_en_body']))
        <div class="lang-fa">{!! $article['content_fa'] !!}</div>
        <div class="lang-en" hidden>{!! $article['content_en'] !!}</div>
      @else
        {!! $article['content_fa'] !!}
      @endif

      <a class="btn-line ar-back" href="{{ esc_url($navUrls['journal'] ?? home_url('/journal/')) }}">
        {!! laico_bi('بازگشت به مجله', 'Back to the journal') !!}
      </a>
    </div>
  </article>

  @if(!empty($relatedArticles))
    <section class="jr jr--related">
      <div class="jr-wrap">
        <div class="jr-head jr-head--grid">
          <h2>{!! laico_bi('بیشتر بخوانید', 'Keep reading') !!}</h2>
        </div>
        <div class="jr-grid jr-grid--two">
          @foreach ($relatedArticles as $rel)
            @include('partials.journal-card', ['item' => $rel, 'featured' => false])
          @endforeach
        </div>
      </div>
    </section>
  @endif
@endsection
