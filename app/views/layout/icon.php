<?php
/**
 * Elle çizilmiş çizgisel ikon seti — dış kütüphane yok.
 * Kullanım: view('layout/icon', ['name' => 'cup', 'class' => 'ico ico--lg'])
 */
$name  = $data['name']  ?? 'cup';
$class = $data['class'] ?? 'ico';

$paths = [
    // fincan + tabak
    'cup' => '<path d="M6 10h13v6a6.5 6.5 0 0 1-13 0z"/><path d="M19 11h2.5a3 3 0 0 1 0 6H19"/><path d="M4 25h17"/><path d="M9 6.5c0-1.6 2-1.6 2-3.2S9 1.8 9 .2"/><path d="M14 6.5c0-1.6 2-1.6 2-3.2S14 1.8 14 .2"/>',
    // V60 dripper
    'dripper' => '<path d="M4 6h20l-7 10h-6z"/><path d="M11 16v4"/><path d="M6.5 26h13a4 4 0 0 0-13 0z"/><path d="M14 2v3"/>',
    // buzlu bardak
    'ice' => '<path d="M7 5h14l-1.6 19a2 2 0 0 1-2 1.8h-6.8a2 2 0 0 1-2-1.8z"/><path d="M10.5 11l3.5 3.5-3.5 3.5"/><path d="M17 12l-3 3 3 3"/><path d="M5 5h18"/>',
    // yaprak / çay
    'leaf' => '<path d="M22 4c0 10-6 16-14 16-2 0-4-.6-4-.6S4 8 15 5c3-.8 7-1 7-1z"/><path d="M18 8L6 22"/>',
    // pasta dilimi
    'cake' => '<path d="M4 13h20v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/><path d="M4 13l10-8 10 8"/><path d="M9 18h.01M14 18h.01M19 18h.01"/>',
    // tabak + çatal
    'plate' => '<circle cx="14" cy="14" r="10"/><circle cx="14" cy="14" r="5.5"/>',
    // kavurma makinesi
    'roaster' => '<rect x="4" y="7" width="16" height="12" rx="6"/><path d="M20 13h4"/><path d="M8 19v5h8v-5"/><path d="M11 13h6"/>',
    // kahve çekirdeği
    'bean' => '<ellipse cx="14" cy="14" rx="9" ry="11" transform="rotate(32 14 14)"/><path d="M9 20c4-5 6-9 10-12"/>',
    'wifi' => '<path d="M3 10a17 17 0 0 1 22 0"/><path d="M7 15a11 11 0 0 1 14 0"/><path d="M11 20a5 5 0 0 1 6 0"/><circle cx="14" cy="24" r="1.2"/>',
    'clock' => '<circle cx="14" cy="14" r="11"/><path d="M14 7v7l4.5 3"/>',
    'pin' => '<path d="M14 26s9-8.5 9-15A9 9 0 1 0 5 11c0 6.5 9 15 9 15z"/><circle cx="14" cy="11" r="3.4"/>',
    'phone' => '<path d="M9 3l3.5 5-2.6 2.4a15 15 0 0 0 7.7 7.7L20 15.5l5 3.5v4a2 2 0 0 1-2.2 2C11.5 24 4 16.5 3 6.2A2 2 0 0 1 5 4z"/>',
    'mail' => '<rect x="3" y="6" width="22" height="16" rx="2"/><path d="M3.5 8l10.5 8L24.5 8"/>',
    'arrow' => '<path d="M4 14h20"/><path d="M17 7l7 7-7 7"/>',
    'search' => '<circle cx="12" cy="12" r="8"/><path d="M18 18l6 6"/>',
    'instagram' => '<rect x="4" y="4" width="20" height="20" rx="6"/><circle cx="14" cy="14" r="5"/><circle cx="20" cy="8" r="1.3" fill="currentColor" stroke="none"/>',
    'facebook' => '<path d="M17 5h-2.5A4.5 4.5 0 0 0 10 9.5V13H7v4h3v10h4V17h3.2l.8-4H14V9.8c0-.5.4-.8.9-.8H18z"/>',
    'whatsapp' => '<path d="M4 24l1.6-5.2A10 10 0 1 1 9.5 22.6z"/><path d="M10.5 11c.4 2.5 3 5.4 5.6 6l1.4-1.6 2.4 1.2c-.4 2-3.5 2.4-5.6 1A11 11 0 0 1 9 11.7c-.3-1.5.3-2.5 1.5-2.5z" fill="currentColor" stroke="none"/>',
    'star' => '<path d="M14 3l3.4 7 7.6 1-5.5 5.3 1.3 7.6L14 20.3 7.2 23.9l1.3-7.6L3 11l7.6-1z"/>',
    // zar — oyunlar
    'dice' => '<rect x="4" y="4" width="20" height="20" rx="4"/><circle cx="9.5" cy="9.5" r="1.4" fill="currentColor" stroke="none"/><circle cx="18.5" cy="9.5" r="1.4" fill="currentColor" stroke="none"/><circle cx="14" cy="14" r="1.4" fill="currentColor" stroke="none"/><circle cx="9.5" cy="18.5" r="1.4" fill="currentColor" stroke="none"/><circle cx="18.5" cy="18.5" r="1.4" fill="currentColor" stroke="none"/>',
    // nargile
    'hookah' => '<path d="M14 2v9"/><path d="M11 5h6"/><path d="M10 11h8l-1 3h-6z"/><path d="M14 14v3"/><path d="M9 25a5 5 0 0 1 10 0z"/><path d="M14 17a4 4 0 0 0-4 4"/><path d="M18 20c3 0 5-2 6-5"/>',
    'quote' =>'<path d="M11 20H4v-7c0-4 2.5-6.5 7-7v3c-2.5.6-3.6 2-3.6 4H11z"/><path d="M24 20h-7v-7c0-4 2.5-6.5 7-7v3c-2.5.6-3.6 2-3.6 4H24z"/>',
];

$d = $paths[$name] ?? $paths['cup'];
?>
<svg class="<?= e($class) ?>" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $d ?></svg>
