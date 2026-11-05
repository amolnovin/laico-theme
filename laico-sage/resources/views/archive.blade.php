@if (is_tax([\App\PostTypes\ProjectPostType::TAX_CATEGORY, \App\PostTypes\ProjectPostType::TAX_SPACE, \App\PostTypes\ProjectPostType::TAX_CITY]))
  @include('archive-laico_project')
@else
  @include('home')
@endif
