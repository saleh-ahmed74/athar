<section class="content-card">
    <p class="hero-eyebrow">{{ $settings->store_name ?: config('app.name') }}</p><h1 class="page-title">{{ $page === 'about' ? __('Our story') : __('Terms and policies') }}</h1>
    @if ($page === 'about')
        @if ($settings->localized('about'))<p style="white-space:pre-wrap">{{ $settings->localized('about') }}</p>@endif
    @else
        @if ($policySections !== [])
            @foreach ($policySections as $section)
                <section class="policy-section">
                    <h2>{{ app()->isLocale('ar') ? $section['title_ar'] : $section['title_en'] }}</h2>
                    <div style="white-space:pre-wrap;line-height:1.8">{{ app()->isLocale('ar') ? $section['body_ar'] : $section['body_en'] }}</div>
                </section>
            @endforeach
        @elseif ($settings->localized('policy'))<div style="white-space:pre-wrap;line-height:1.8">{{ $settings->localized('policy') }}</div>@endif
    @endif
    @if ($settings->support_email || ($page === 'about' && $settings->support_phone))
        <div style="margin-top:20px"><h2>{{ __('Need help?') }}</h2>@if($settings->support_email)<p><a href="mailto:{{ $settings->support_email }}">{{ $settings->support_email }}</a></p>@endif @if($page === 'about' && $settings->support_phone)
            @php($whatsappHref = $settings->whatsapp_url ?: 'https://wa.me/'.preg_replace('/\D+/', '', $settings->support_phone))
            <p><a class="whatsapp-order-link about-whatsapp-link" href="{{ $whatsappHref }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Contact us on WhatsApp') }}" title="{{ __('Contact us on WhatsApp') }}"><svg viewBox="0 0 448 512" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32 101.5 32 1.9 131.6 1.9 254c0 39.1 10.2 77.3 29.6 111L0 480l118-30.9c32.5 17.7 69.1 27 105.8 27h.1c122.4 0 222-99.6 222-222 0-59.3-23.1-115.1-65-157zM223.9 438c-32.8 0-65-8.8-93-25.4l-6.7-4-70 18.3 18.7-68.2-4.4-7C50 322.2 40 288.6 40 254 40 152.6 122.5 70 223.9 70c49.1 0 95.2 19.1 129.9 53.9 34.7 34.7 53.8 80.9 53.8 130 0 101.4-82.5 184.1-183.7 184.1zm101-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.7-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.4-65.9-5.7-9.8 5.7-9.1 16.3-30.4 1.8-3.7.9-6.9-.5-9.7s-12.5-30.1-17.1-41.2c-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2s-9.7 1.4-14.8 6.9c-5.1 5.6-19.4 19-19.4 46.3s19.9 53.8 22.6 57.5c2.8 3.7 39.2 59.8 95 83.9 35.3 15.2 49.1 16.5 66.7 13.9 10.7-1.6 32.8-13.4 37.4-26.4 2.4-6.8 2.4-12.6 1.6-13.8-1-1.6-3.3-2.4-7.7-4.6z"></path></svg><span>{{ __('WhatsApp') }}</span></a></p>
        @endif</div>
    @endif
</section>
