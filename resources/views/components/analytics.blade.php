@php $gaId = config('services.google_analytics.id') @endphp
@if($gaId)
{{-- Google Analytics 4 — chỉ hiển thị khi GOOGLE_ANALYTICS_ID được cấu hình --}}
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $gaId }}');
</script>
@endif
