<?php

$tt_text    = $tt_text ?? '';
$tt_trigger = $tt_trigger ?? '';
$tt_wrap    = $tt_wrap ?? '';
?>
<span class="relative inline-flex items-center gap-1" x-data="{ tt: false }" x-on:mouseenter="tt = true" x-on:mouseleave="tt = false"<?php echo $tt_wrap ? ' ' . $tt_wrap : ''; ?>>
    <?php echo $tt_trigger; ?>
    <span x-show="tt" x-cloak x-transition class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1 -translate-x-1/2 whitespace-nowrap rounded bg-neutral-900 px-2 py-1 text-xs text-white"><?php echo esc_html($tt_text); ?></span>
</span>
<?php $tt_text = $tt_trigger = $tt_wrap = null; ?>
