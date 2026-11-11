<?php
// includes/ikon.php — ikon maskot. Pakai: echo ikon('V');  (V = Visual, A = Auditori, K = Kinestetik)
function ikon(string $kode): string {
    switch (strtoupper($kode)) {
        case 'V':
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><ellipse cx="32" cy="32" rx="28" ry="18" fill="#fff"/><circle cx="32" cy="32" r="12" fill="#8B7CF0"/><circle cx="32" cy="32" r="5" fill="#2D3142"/><circle cx="36" cy="28" r="2.5" fill="#fff"/></svg>';
        case 'A':
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M20 30c0-9 5-16 13-16s13 7 13 15c0 9-8 10-8 17a7 7 0 0 1-14 0" fill="#FF9F7A" stroke="#2D3142" stroke-width="3" stroke-linecap="round"/><path d="M29 30c0-4 2-6 5-6" fill="none" stroke="#2D3142" stroke-width="3" stroke-linecap="round"/></svg>';
        default:
            return '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M18 34V18a4 4 0 0 1 8 0v10m0-12a4 4 0 0 1 8 0v12m0-10a4 4 0 0 1 8 0v14m0-6a4 4 0 0 1 8 0v14c0 10-7 16-16 16-10 0-16-6-20-14l-4-8a4 4 0 0 1 7-3z" fill="#5CCB9C" stroke="#2D3142" stroke-width="3" stroke-linejoin="round" stroke-linecap="round"/></svg>';
    }
}
