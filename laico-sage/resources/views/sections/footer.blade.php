@php
  $footerLinks = [
    ['url' => $navUrls['services'] ?? home_url('/services/'), 'fa' => 'طراحی پلان', 'en' => 'Space planning'],
    ['url' => $navUrls['kitchen'] ?? home_url('/kitchen/'), 'fa' => 'آشپزخانه', 'en' => 'Kitchen'],
    ['url' => $navUrls['bath'] ?? home_url('/bath/'), 'fa' => 'سرویس و حمام', 'en' => 'Bath'],
    ['url' => $navUrls['living'] ?? home_url('/living/'), 'fa' => 'پذیرایی و اتاق خواب', 'en' => 'Living & bedroom'],
    ['url' => $navUrls['cafe'] ?? home_url('/cafe/'), 'fa' => 'کافه و رستوران', 'en' => 'Café & restaurant'],
  ];

  $phoneDisplay = (string) ($themeOpt['phone_primary'] ?? '۰۹۱۲۰۸۹۸۹۳۸');
  $phoneTel = preg_replace('/[^\d+]/', '', (string) ($themeOpt['phone_primary_tel'] ?? '09120898938')) ?: '09120898938';
  $wa = trim((string) ($themeOpt['whatsapp_url'] ?? ''));
  $tg = trim((string) ($themeOpt['telegram_url'] ?? ''));
  $ig = trim((string) ($themeOpt['instagram_url'] ?? ''));
  $yt = trim((string) ($themeOpt['youtube_url'] ?? ''));
@endphp

<footer class="site-footer">
  <div class="footer-main">
    <nav class="footer-links" aria-label="footer">
      @foreach ($footerLinks as $link)
        <a href="{{ esc_url($link['url']) }}">{!! laico_bi($link['fa'], $link['en']) !!}</a>
      @endforeach
    </nav>

    <div class="footer-brand">
      <a class="footer-logo" href="{{ esc_url($navUrls['home'] ?? home_url('/')) }}" aria-label="Laico Design">
        <img src="{{ esc_url($logoUrl) }}" alt="{{ esc_attr($siteName) }}" width="357" height="232" loading="lazy">
      </a>
      <p>{!! laico_bi((string) ($themeOpt['footer_tagline_fa'] ?? ''), (string) ($themeOpt['footer_tagline_en'] ?? '')) !!}</p>

      <div class="footer-social">
        @if ($wa !== '')
          <a class="soc" href="{{ esc_url($wa) }}" target="_blank" rel="noopener" aria-label="واتس‌اپ">
            <i class="ri-whatsapp-line" style="font-size: 1.5rem;"></i>
          </a>
        @endif
        @if ($tg !== '')
          <a class="soc" href="{{ esc_url($tg) }}" target="_blank" rel="noopener" aria-label="تلگرام">
            <i class="ri-telegram-line" style="font-size: 1.5rem;"></i>
          </a>
        @endif
        @if ($ig !== '')
          <a class="soc" href="{{ esc_url($ig) }}" target="_blank" rel="noopener" aria-label="اینستاگرام">
            <i class="ri-instagram-line" style="font-size: 1.5rem;"></i>
          </a>
        @endif
        @if ($yt !== '' && $yt !== '#')
          <a class="soc" href="{{ esc_url($yt) }}" target="_blank" rel="noopener" aria-label="یوتیوب">
            <i class="ri-youtube-line" style="font-size: 1.5rem;"></i>
          </a>
        @endif
      </div>
    </div>

    <div class="footer-contact" style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px; direction: ltr;">
      <p style="margin: 0; text-align: left; direction: rtl;">
        {!! laico_bi((string) ($themeOpt['address_fa'] ?? ''), (string) ($themeOpt['address_en'] ?? '')) !!}
      </p>
      <a class="footer-phone" href="tel:{{ esc_attr($phoneTel) }}" style="font-family: 'Vazirmatn', sans-serif; margin: 0; text-align: left; direction: ltr; line-height: 1;">
        {{ $phoneDisplay }}
      </a>
    </div>
  </div>

  <div class="footer-legal">
    <p>{!! laico_bi((string) ($themeOpt['footer_copy_fa'] ?? ''), (string) ($themeOpt['footer_copy_en'] ?? '')) !!}</p>
    <p>
      <a href="{{ esc_url($navUrls['privacy'] ?? home_url('/privacy/')) }}">{!! laico_bi('حریم خصوصی', 'Privacy') !!}</a>
      &nbsp;·&nbsp;
      <a href="{{ esc_url($navUrls['cookies'] ?? home_url('/cookies/')) }}">{!! laico_bi('کوکی‌ها', 'Cookies') !!}</a>
    </p>
    <p>{!! laico_bi((string) ($themeOpt['footer_credit_fa'] ?? ''), (string) ($themeOpt['footer_credit_en'] ?? '')) !!}</p>
  </div>
</footer>
