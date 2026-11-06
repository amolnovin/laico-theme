@php
  $captchaHtml = \App\Services\SecurityIntegrationService::renderMathCaptcha();
@endphp
@if ($captchaHtml !== '')
  <div class="laico-captcha-slot" data-field="captcha">
    {!! $captchaHtml !!}
    <span class="field-error" role="alert"></span>
  </div>
@endif
