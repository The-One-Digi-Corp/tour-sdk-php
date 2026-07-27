<div style="background:#FFF7E0;border-left:8px solid #F3C024;padding:16px 16px 16px 24px;border-radius:4px;margin-top:24px;">
    <div class="fs-h4 fw-semibold" style="color:#F3C024;margin-bottom:8px;">
        {{ $title ?? "Need help? We're here for you" }}
    </div>
    <div class="fs-lg">
        {{ $description ?? 'Should you have any questions or require assistance, reply to this email and our team will help.' }}
    </div>
    @if (!empty($subtext))
        <div class="fs-lg" style="margin-top:24px;">{{ $subtext }}</div>
    @endif
</div>
