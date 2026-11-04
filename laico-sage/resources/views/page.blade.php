@php
  $queried = get_queried_object();
  $slug = $queried instanceof \WP_Post ? (string) $queried->post_name : '';

  $autoSlugMap = [
    'services' => 'template-services',
    'projects' => 'archive-laico_project',
    'about' => 'template-about',
    'contact' => 'template-contact',
    'showroom' => 'template-showroom',
    'kitchen' => 'template-collection',
    'living' => 'template-collection',
    'bath' => 'template-collection',
    'cafe' => 'template-collection',
    'studio' => 'template-collection',
    'systems' => 'template-collection',
    'process' => 'template-collection',
    'materials' => 'template-collection',
    'privacy' => 'template-legal',
    'cookies' => 'template-legal',
  ];
@endphp

@if (isset($autoSlugMap[$slug]))
  @include($autoSlugMap[$slug])
@else
  @extends('layouts.app')

  @section('content')
    @while(have_posts()) @php(the_post())
      @php
        $pid = get_the_ID();
        $hasThumb = has_post_thumbnail($pid);
      @endphp

      @if ($hasThumb)
        @php($pageHero = \App\Support\Hero::resolve($slug ?: 'page', (int) $pid))
        @include('partials.hero-page', ['hero' => $pageHero])
      @else
        <section class="pd-top ar-top">
          <div class="ar-wrap">
            <h1>{{ get_the_title() }}</h1>
          </div>
        </section>
      @endif

      <section class="wrap prose">
        @php(the_content())
      </section>
    @endwhile
  @endsection
@endif
