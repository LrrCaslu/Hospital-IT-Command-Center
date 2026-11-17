<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name'=>'activity','size'=>18]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['name'=>'activity','size'=>18]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
$paths=[
 'activity'=>'<path d="M22 12h-4l-3 9-6-18-3 9H2"/>',
 'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
 'building'=>'<path d="M4 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M16 8h2a2 2 0 0 1 2 2v12M2 22h20M8 6h4M8 10h4M8 14h4M8 18h4"/>',
 'network'=>'<rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4M5 16v-4h14v4"/>',
 'globe'=>'<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a16 16 0 0 1 0 20M12 2a16 16 0 0 0 0 20"/>',
 'wifi'=>'<path d="M2 8.82a15 15 0 0 1 20 0M5 12.86a10 10 0 0 1 14 0M8.5 16.86a5 5 0 0 1 7 0M12 20h.01"/>',
 'server'=>'<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><path d="M6 6h.01M6 18h.01M10 6h8M10 18h8"/>',
 'ticket'=>'<path d="M2 9V6a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a3 3 0 0 0 0 6v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-3a3 3 0 0 0 0-6ZM13 5v2M13 10v4M13 17v2"/>',
 'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
 'database'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5M3 12a9 3 0 0 0 18 0"/>',
 'alert'=>'<path d="m10.3 3.8-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.2l-8-14a2 2 0 0 0-3.4 0ZM12 9v4M12 17h.01"/>',
 'power'=>'<path d="M12 2v10M18.4 6.4a9 9 0 1 1-12.8 0"/>',
 'check'=>'<path d="M20 6 9 17l-5-5"/>',
 'clock'=>'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
 'chart'=>'<path d="M3 3v18h18M7 16l4-4 4 2 5-7"/>',
 'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
 'settings'=>'<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8ZM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06-1.8 1.8-.06-.06A1.65 1.65 0 0 0 16.1 18.3l-.09 2.5h-2.54l-.09-.07a1.65 1.65 0 0 0-2.76 0l-.09.07H8l-.09-2.5a1.65 1.65 0 0 0-1.83.33l-.06.06-1.8-1.8.06-.06A1.65 1.65 0 0 0 4.6 15l-2.5-.1v-2.5l2.5-.1a1.65 1.65 0 0 0-.33-1.8l-.06-.06 1.8-1.8.06.06a1.65 1.65 0 0 0 1.83.33L8 6.5h2.53l.09.07a1.65 1.65 0 0 0 2.76 0l.09-.07H16l.1 2.5a1.65 1.65 0 0 0 1.82-.33l.06-.06 1.8 1.8-.06.06a1.65 1.65 0 0 0-.33 1.82l2.5.1v2.5z"/>',
 'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>',
 'search'=>'<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
 'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
 'arrow'=>'<path d="m9 18 6-6-6-6"/>',
 'plus'=>'<path d="M12 5v14M5 12h14"/>',
 'refresh'=>'<path d="M20 11a8 8 0 0 0-14.9-4M4 4v4h4M4 13a8 8 0 0 0 14.9 4M20 20v-4h-4"/>',
 'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
 'monitor'=>'<rect x="2" y="3" width="20" height="15" rx="2"/><path d="M8 21h8M12 18v3"/>',
 'lock'=>'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
];
?>
<svg aria-hidden="true" width="<?php echo e($size); ?>" height="<?php echo e($size); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" <?php echo e($attributes); ?>><?php echo $paths[$name]??$paths['activity']; ?></svg>
<?php /**PATH C:\Users\Estatísticas\Downloads\Hospital_IT_Command_Center_ServBay_CACHE_CORRIGIDO\hospital-it-command-center\resources\views/components/icon.blade.php ENDPATH**/ ?>