@php
  $sourceLabel = $sourcePage ?? 'درباره ما / تماس';
  $panelEnd = !empty($panelAtEnd);
  $noteId = 'consult-note-' . sanitize_key($sourceLabel);
  $titleId = 'consult-title-' . sanitize_key($sourceLabel);
@endphp

<section class="about-consult" aria-labelledby="{{ esc_attr($titleId) }}">
  <div class="about-consult-grid has-photo">
    <div class="consult-photo">
      <img src="{{ esc_url(laico_asset('images/contact-form.jpg')) }}" alt="نشیمن با مبل سرمه‌ای و نمای شهر" width="452" height="560" loading="lazy">
    </div>

    @if (!$panelEnd)
      <div class="about-consult-panel">
        <p id="{{ esc_attr($titleId) }}">
          <span class="lang-fa">با ما<br>مشورت<br>کنید..</span>
          <span class="lang-en" hidden>Consult<br>with us..</span>
        </p>
      </div>
    @endif

    <div class="about-consult-form">
      <form data-form novalidate>
        <input type="hidden" name="source_page" value="{{ esc_attr($sourceLabel) }}">
        <input type="hidden" name="type" value="">
        <div class="laico-hp-field" aria-hidden="true">
          <input type="text" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        <label class="about-field" data-field="name">
          <span class="visually-hidden">{!! laico_bi('نام و نام خانوادگی', 'Full name') !!}</span>
          <input name="name" type="text" autocomplete="name" placeholder="نام و نام خانوادگی" data-i18n-placeholder="f-name" required>
          <span class="field-error"></span>
        </label>
        <label class="about-field" data-field="phone">
          <span class="visually-hidden">{!! laico_bi('شماره تماس', 'Phone') !!}</span>
          <input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="شماره تماس" data-i18n-placeholder="f-phone" required>
          <span class="field-error"></span>
        </label>
        <div class="about-field about-field-note" data-field="note">
          <label class="visually-hidden" for="{{ esc_attr($noteId) }}">{!! laico_bi('متن پیام', 'Message') !!}</label>
          <div class="about-note-box">
            <textarea id="{{ esc_attr($noteId) }}" name="note" rows="4" placeholder="متن پیام را بنویسید.." data-i18n-placeholder="about-ph-note" required></textarea>
            <button class="about-submit" type="submit">{!! laico_bi('ثبت درخواست', 'Submit request') !!}</button>
          </div>
          <span class="field-error"></span>
        </div>
      </form>
      <div class="about-done" data-success hidden>
        <h3 data-ok-title data-i18n="f-ok-title">{{ $themeOpt['form_success_title_fa'] ?? 'درخواست شما ثبت شد.' }}</h3>
        <p data-ok-body data-i18n="f-ok-body">{{ $themeOpt['form_success_body_fa'] ?? 'تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.' }}</p>
      </div>
    </div>

    @if ($panelEnd)
      <div class="about-consult-panel">
        <p id="{{ esc_attr($titleId) }}">
          <span class="lang-fa">با ما<br>مشورت<br>کنید..</span>
          <span class="lang-en" hidden>Consult<br>with us..</span>
        </p>
      </div>
    @endif
  </div>
</section>
