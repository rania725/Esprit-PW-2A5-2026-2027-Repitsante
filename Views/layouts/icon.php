<?php
$icons = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/><circle cx="9" cy="7" r="4"/>',
    'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
    'layers' => '<path d="m12 2 10 6-10 6L2 8l10-6Zm10 11-10 6-10-6m20 5-10 5-10-5"/>',
    'refresh' => '<path d="M20 7a9 9 0 0 0-15 0L3 9m18-6v6h-6M4 17a9 9 0 0 0 15 0l2-2M3 21v-6h6"/>',
    'route' => '<circle cx="5" cy="19" r="3"/><circle cx="18" cy="5" r="3"/><path d="M8 19c8 0 10-4 10-11"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12a2 2 0 0 0 4 0"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="m10 2-1 3-2 1-3-1-2 4 2 2v2l-2 2 2 4 3-1 2 1 1 3h4l1-3 2-1 3 1 2-4-2-2v-2l2-2-2-4-3 1-2-1-1-3h-4Z"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/>',
    'message' => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>',
    'chevron' => '<path d="m9 18 6-6-6-6"/>',
    'down' => '<path d="m6 9 6 6 6-6"/>',
    'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5h4"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
    'edit' => '<path d="m15 5 4 4M3 21l1-5L16 4a2.8 2.8 0 0 1 4 4L8 20l-5 1Z"/>',
    'trash' => '<path d="M3 6h18M9 6V3h6v3m4 0-1 15H6L5 6m5 4v7m4-7v7"/>',
    'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4"/>',
    'exit' => '<path d="M10 4H4v16h6m4-12 4 4-4 4m-6-4h12"/>',
];
?>
<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$name] ?? '' ?></svg>
