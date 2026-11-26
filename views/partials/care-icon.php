<?php
$careIconMarkup = match ($careIcon ?? '') {
    'digestive' => '<path d="M10 3v5c0 1.3-.8 2.2-2.1 2.2-2 0-3.9 1.8-3.9 4.7C4 18.3 7.2 21 11 21c5.8 0 9-4 9-8.5 0-4.1-2.5-7-5-7-1.5 0-2.2.9-2.2 2.2V3"/><path d="M8 15c1.5 2 3.7 2.7 6.2 1.3"/>',
    'metabolic' => '<circle cx="12" cy="12" r="9.5"/><path d="M4 12h4l2.1-5 3.7 10 2.1-5H20"/>',
    'habits' => '<path d="M20.5 10a8.6 8.6 0 0 0-15.6-3L3 10m0-5v5h5m-4.5 4a8.6 8.6 0 0 0 15.6 3L21 14m0 5v-5h-5"/><path d="m9 12 2 2 4-4"/>',
    'people' => '<circle cx="10" cy="7" r="3"/><path d="M3 20v-2.5a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5V20M16 4a3 3 0 0 1 0 6m4 10v-2.5a5 5 0 0 0-2.5-4.3"/>',
    'program' => '<path d="M4 4h7a3 3 0 0 1 3 3v13a4 4 0 0 0-3-1H4zM14 7a3 3 0 0 1 3-3h3v15h-3a4 4 0 0 0-3 1M7 9h4m-4 4h4"/>',
    'focus' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 1v4m0 14v4M1 12h4m14 0h4"/>',
    'clinician' => '<path d="M6 3v6a5 5 0 0 0 10 0V3M4 3h4m6 0h4M11 14v3a4 4 0 0 0 8 0v-3"/><circle cx="19" cy="12" r="2"/>',
    'phone' => '<path d="M6 3H3v2c0 9 7 16 16 16h2v-3l-5-3-2 2a12 12 0 0 1-7-7l2-2z"/>',
    'message' => '<path d="M21 11.5a9 9 0 0 1-9 9 10 10 0 0 1-3.5-.7L3 21l1.2-5A9 9 0 1 1 21 11.5Z"/><path d="M8 9h8m-8 4h5"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 6 9 7 9-7"/>',
    default => '',
};
if ($careIconMarkup !== ''): ?>
<svg class="nebo-care-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"><?= $careIconMarkup ?></svg>
<?php endif; ?>
