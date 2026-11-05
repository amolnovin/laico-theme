@php
  $phoneDisplay = (string) ($themeOpt['phone_primary'] ?? '۰۹۱۲۰۸۹۸۹۳۸');
  $phoneTel = preg_replace('/[^\d+]/', '', (string) ($themeOpt['phone_primary_tel'] ?? '09120898938')) ?: '09120898938';
  $sourceLabel = $sourcePage ?? 'خدمات / پروژه‌ها';
@endphp

<section class="svc-consult" id="svc-consult" aria-labelledby="svc-consult-title">
  <div class="svc-consult-grid">
    <div class="svc-panel">
      <p class="svc-panel-eyebrow">{!! laico_bi('درخواست مشاوره تخصصی', 'Request a consultation') !!}</p>
      <h2 id="svc-consult-title">{!! laico_bi('چطور می‌توانیم به شما کمک کنیم؟', 'How can we help you?') !!}</h2>
      <hr class="svc-rule">
      <div class="svc-info">
        <span class="svc-info-label">{!! laico_bi('تماس مستقیم با ما', 'Call us directly') !!}</span>
        <span class="svc-info-value">
          <a href="tel:{{ esc_attr($phoneTel) }}" dir="ltr">{!! laico_bi($phoneDisplay, '+98 912 089 8938') !!}</a>
        </span>
      </div>
      <div class="svc-info">
        <span class="svc-info-label">{!! laico_bi('آدرس دفتر', 'Office address') !!}</span>
        <span class="svc-info-value">{!! laico_bi((string) ($themeOpt['address_fa'] ?? ''), (string) ($themeOpt['address_en'] ?? '')) !!}</span>
      </div>
    </div>

    <div class="svc-form-col">
      <form class="svc-form" data-form novalidate>
        <input type="hidden" name="source_page" value="{{ esc_attr($sourceLabel) }}">
        <input type="hidden" name="type" value="{{ esc_attr($defaultType ?? '') }}">
        <div style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">
          <input type="text" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <label class="svc-field" data-field="name">
          <span class="visually-hidden">{!! laico_bi('نام و نام خانوادگی', 'Full name') !!}</span>
          <input type="text" name="name" autocomplete="name" placeholder="نام و نام خانوادگی" data-i18n-placeholder="f-name" required>
          <span class="field-error"></span>
        </label>
        <label class="svc-field" data-field="phone">
          <span class="visually-hidden">{!! laico_bi('شماره تماس', 'Phone') !!}</span>
          <input type="tel" name="phone" inputmode="tel" autocomplete="tel" placeholder="شماره تماس" data-i18n-placeholder="f-phone" required>
          <span class="field-error"></span>
        </label>
        <label class="svc-field" data-field="note">
          <span class="visually-hidden">{!! laico_bi('متن پیام', 'Message') !!}</span>
          <textarea name="note" rows="4" placeholder="متن پیام را بنویسید.." data-i18n-placeholder="about-ph-note" required></textarea>
          <span class="field-error"></span>
        </label>
        <button class="svc-submit" type="submit">{!! laico_bi('ثبت درخواست', 'Submit request') !!}</button>
      </form>
      <div class="svc-done" data-success hidden>
        <h3 data-ok-title data-i18n="f-ok-title">{{ $themeOpt['form_success_title_fa'] ?? 'درخواست شما ثبت شد.' }}</h3>
        <p data-ok-body data-i18n="f-ok-body">{{ $themeOpt['form_success_body_fa'] ?? 'تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.' }}</p>
      </div>
    </div>
  </div>
</section>
