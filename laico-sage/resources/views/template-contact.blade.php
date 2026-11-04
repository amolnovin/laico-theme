{{--
  Template Name: لایکو — تماس با ما (Contact)
--}}
@extends('layouts.app')

@section('content')
  @php
    $pageId = (int) get_queried_object_id();
    $hero = \App\Support\Hero::resolve('contact', $pageId);

    $phoneDisplay = (string) ($themeOpt['phone_primary'] ?? '۰۹۱۲۰۸۹۸۹۳۸');
    $phoneTel = preg_replace('/[^\d+]/', '', (string) ($themeOpt['phone_primary_tel'] ?? '09120898938')) ?: '09120898938';
    $wa = trim((string) ($themeOpt['whatsapp_url'] ?? 'https://wa.me/989120898938'));
    $tg = trim((string) ($themeOpt['telegram_url'] ?? 'https://t.me/Laico_design'));
    $ig = trim((string) ($themeOpt['instagram_url'] ?? 'https://www.instagram.com/Laico_design/'));
  @endphp

  {{-- Dynamic Hero Image from Contact Page's Featured Image + Floating Contact Card --}}
  @component('partials.hero-page', ['hero' => $hero, 'extraHeroClass' => 'hero--contact'])
    <div class="contact-card">
      <span class="contact-rule" aria-hidden="true"></span>
      <div class="contact-col contact-col-info">
        <a class="contact-line" href="tel:{{ esc_attr($phoneTel) }}">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.2 3.6h2.6l1.1 2.8-1.8 1.1a11 11 0 0 0 5.2 5.2l1.1-1.8 2.8 1.1v2.6a1.8 1.8 0 0 1-2 1.8A13.2 13.2 0 0 1 6.4 5.6a1.8 1.8 0 0 1 1.8-2z"/></svg>
          <span class="contact-phone">{{ $phoneDisplay }}</span>
        </a>
        <p class="contact-line">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6.2-5.1 6.2-10.1a6.2 6.2 0 1 0-12.4 0C5.8 15.9 12 21 12 21z"/><circle cx="12" cy="10.8" r="2.1"/></svg>
          <span>{!! laico_bi((string) ($themeOpt['address_fa'] ?? ''), (string) ($themeOpt['address_en'] ?? '')) !!}</span>
        </p>
      </div>

      <div class="contact-col contact-col-hours" style="text-align: center;">
        <p class="contact-line" style="justify-content: center;">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="6.4"/><path d="M12 10.1V13l2.3 1.5"/><path d="M16.2 4.4a8.6 8.6 0 0 1 4.2 3.6"/><path d="M17.6 3.4l3.1 1.1-1.8 2.4"/></svg>
          <span>{!! laico_bi((string) ($themeOpt['hours_fa'] ?? ''), (string) ($themeOpt['hours_en'] ?? '')) !!}</span>
        </p>
        <div class="contact-social" style="justify-content: center;">
          @if ($wa !== '')
            <a href="{{ esc_url($wa) }}" target="_blank" rel="noopener" aria-label="واتس‌اپ">
              <i class="ri-whatsapp-line" style="font-size: 1.5rem;"></i>
            </a>
          @endif
          @if ($ig !== '')
            <a href="{{ esc_url($ig) }}" target="_blank" rel="noopener" aria-label="اینستاگرام">
              <i class="ri-instagram-line" style="font-size: 1.5rem;"></i>
            </a>
          @endif
          @if ($tg !== '')
            <a href="{{ esc_url($tg) }}" target="_blank" rel="noopener" aria-label="تلگرام">
              <i class="ri-telegram-line" style="font-size: 1.5rem;"></i>
            </a>
          @endif
        </div>
      </div>
    </div>
  @endcomponent

  <section class="contact-map" id="contact-map" aria-label="نقشه" style="text-align: center;">
    <iframe
      src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2548.1376192675475!2d52.3449366!3d36.4781646!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3f8f97ad81a3b509%3A0x4970e7d40866b421!2sLaico_design!5e1!3m2!1sen!2s!4v1793627903188!5m2!1sen!2s"
      width="90%"
      height="450"
      style="border:0; max-width: 1400px; margin: 0 auto; border-radius: 8px;"
      allowfullscreen=""
      loading="lazy"
      title="موقعیت شو روم لایکو دیزاین روی نقشه"
      referrerpolicy="strict-origin-when-cross-origin"></iframe>
  </section>

  @include('partials.about-consult', ['sourcePage' => 'تماس با ما', 'panelAtEnd' => true])
@endsection
