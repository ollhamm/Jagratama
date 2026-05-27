<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Tanda Tangan — Jagratama</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { background: #fff; border-radius: 1.25rem; border: 1px solid #e4e7ec; max-width: 480px; width: 100%; padding: 2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.07); }
        .badge { display: inline-flex; align-items: center; gap: 0.4rem; background: #ecfdf3; color: #027a48; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; padding: 0.25rem 0.75rem; margin-bottom: 1.25rem; }
        .badge svg { width: 1rem; height: 1rem; }
        h1 { font-size: 1.125rem; font-weight: 700; color: #101828; margin-bottom: 0.25rem; }
        .subtitle { font-size: 0.8125rem; color: #667085; margin-bottom: 1.5rem; }
        .sig-img { display: block; margin: 0 auto 1.5rem; max-height: 140px; max-width: 100%; border: 1px solid #e4e7ec; border-radius: 0.75rem; background: #fff; padding: 0.5rem; }
        .info-grid { display: grid; gap: 0.75rem; }
        .info-row { display: flex; flex-direction: column; gap: 0.125rem; background: #f9fafb; border-radius: 0.625rem; padding: 0.75rem 1rem; }
        .info-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #98a2b3; }
        .info-value { font-size: 0.875rem; font-weight: 500; color: #344054; }
        .footer { margin-top: 1.5rem; text-align: center; font-size: 0.75rem; color: #98a2b3; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Tanda Tangan Terverifikasi
        </div>

        <h1>{{ $signature->role_name }}</h1>
        <p class="subtitle">Ditandatangani oleh {{ $signature->signer_name }}</p>

        <img src="{{ $signature->signature_value }}" alt="Tanda Tangan" class="sig-img">

        <a href="{{ $signature->signature_value }}"
            download="ttd-{{ \Illuminate\Support\Str::slug($signature->signer_name) }}.png"
            style="display:block; width:100%; padding:0.625rem 1rem; background:#25949d; color:#fff; text-align:center; border-radius:0.625rem; font-size:0.875rem; font-weight:600; text-decoration:none; margin-bottom:1.25rem;">
            Download Tanda Tangan (png)
        </a>

        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Penandatangan</span>
                <span class="info-value">{{ $signature->signer_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Jabatan / Role</span>
                <span class="info-value">{{ $signature->role_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dokumen</span>
                <span class="info-value">{{ $signature->document->title ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tipe Dokumen</span>
                <span class="info-value">{{ $signature->document->documentType->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Organisasi</span>
                <span class="info-value">{{ $signature->document->organization->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Waktu Tanda Tangan</span>
                <span class="info-value">{{ $signature->signed_at?->format('d/m/Y H:i') ?? '-' }} WIB</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID Verifikasi</span>
                <span class="info-value" style="font-size:0.75rem;word-break:break-all;">{{ $signature->id }}</span>
            </div>
        </div>

        <p class="footer">Jagratama &mdash; Sistem Pengelolaan Dokumen</p>
    </div>
</body>
</html>
