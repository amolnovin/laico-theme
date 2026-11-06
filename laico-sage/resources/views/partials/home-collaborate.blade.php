@php
  $sourceLabel = $sourcePage ?? 'صفحه اصلی';
  $selectedType = $defaultType ?? 'kitchen';
  $typeOptions = [
    'kitchen' => ['i18n' => 'opt-kitchen', 'fa' => 'آشپزخانه'],
    'living' => ['i18n' => 'opt-living', 'fa' => 'پذیرایی و اتاق'],
    'bath' => ['i18n' => 'opt-bath', 'fa' => 'حمام'],
    'cafe' => ['i18n' => 'opt-cafe', 'fa' => 'کافه و رستوران'],
    'wardrobe' => ['i18n' => 'opt-wardrobe', 'fa' => 'کمد و سیستم'],
    'full' => ['i18n' => 'opt-full', 'fa' => 'بازسازی کامل'],
  ];
  $currentOpt = $typeOptions[$selectedType] ?? $typeOptions['kitchen'];
@endphp

<section class="collaborate" id="collaborate">
  <div>
    <p class="eyebrow" data-i18n="form-eye">۰۶ — شروع پروژه</p>
    <h2><span data-i18n="form-1">چطور می‌توانیم</span><br><span data-i18n="form-2">همکاری کنیم؟</span></h2>
    <p class="body" data-i18n="form-body">فرم را پر کنید؛ تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.</p>
  </div>
  <div>
    <form class="form" data-form novalidate>
      <input type="hidden" name="source_page" value="{{ esc_attr($sourceLabel) }}">
      <div class="laico-hp-field" aria-hidden="true">
        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
      </div>

      <div class="field" data-field="name">
        <span class="field-label" data-i18n="f-name">نام و نام خانوادگی</span>
        <input type="text" name="name" autocomplete="name" placeholder="—" required>
        <span class="field-error"></span>
      </div>

      <div class="field" data-field="phone">
        <span class="field-label" data-i18n="f-phone">شماره تماس</span>
        <div class="phone-line">
          <span class="prefix">+98</span>
          <input type="tel" name="phone" inputmode="tel" autocomplete="tel" dir="ltr" required>
        </div>
        <span class="field-error"></span>
      </div>

      <div class="field" data-field="type">
        <span class="field-label" data-i18n="f-type">نوع پروژه</span>
        <div class="select" data-select>
          <button type="button" class="select-btn" aria-haspopup="listbox" aria-expanded="false">
            <span class="select-value" data-i18n="{{ esc_attr($currentOpt['i18n']) }}">{{ $currentOpt['fa'] }}</span>
            <i class="chev" aria-hidden="true"></i>
          </button>
          <ul class="select-menu" role="listbox" hidden>
            @foreach ($typeOptions as $val => $meta)
              <li>
                <button type="button"
                        data-value="{{ esc_attr($val) }}"
                        data-i18n="{{ esc_attr($meta['i18n']) }}"
                        aria-selected="{{ $val === $selectedType ? 'true' : 'false' }}">{{ $meta['fa'] }}</button>
              </li>
            @endforeach
          </ul>
          <input type="hidden" name="type" value="{{ esc_attr($selectedType) }}">
        </div>
      </div>

      <div class="field" data-field="note">
        <span class="field-label" data-i18n="f-note">توضیح کوتاه</span>
        <textarea name="note" data-i18n-placeholder="f-note-ph" placeholder="متراژ، شهر، بازه زمانی" required></textarea>
        <span class="field-error"></span>
      </div>

      @include('partials.math-captcha')

      <button class="btn-solid" type="submit" data-i18n="f-send">ارسال درخواست</button>
    </form>

    <div class="form-success" data-success hidden>
      <h3 data-ok-title data-i18n="f-ok-title">{{ $themeOpt['form_success_title_fa'] ?? 'درخواست شما ثبت شد.' }}</h3>
      <p data-ok-body data-i18n="f-ok-body">{{ $themeOpt['form_success_body_fa'] ?? 'تیم طراحی ظرف ۴۸ ساعت کاری برای جلسه اول با شما تماس می‌گیرد.' }}</p>
    </div>
  </div>
</section>
