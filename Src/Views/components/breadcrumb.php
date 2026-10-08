<?php

$total = count($crumbs);
?>
<nav aria-label="breadcrumb" class="no-scrollbar mb-6 flex items-center gap-1 overflow-x-auto text-sm text-neutral-500">
    <?php foreach ($crumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?>
            <?php echo \Contabai\Heroicon::outline('chevron-right', 'w-4 h-4 flex-none text-neutral-400'); ?>
        <?php endif; ?>
        <?php if (! empty($crumb['url']) && $i < $total - 1): ?>
            <a href="<?php echo esc_url($crumb['url']); ?>"<?php echo $i === 0 ? ' aria-label="' . esc_attr($crumb['label']) . '"' : ''; ?> class="inline-flex flex-none items-center whitespace-nowrap py-1 no-underline transition hover:text-neutral-900"><?php echo $i === 0 ? \Contabai\Heroicon::outline('home', 'w-4 h-4') : esc_html($crumb['label']); ?></a>
        <?php else: ?>
            <span class="inline-flex flex-none items-center whitespace-nowrap py-1 font-medium text-neutral-600"><?php echo $i === 0 ? \Contabai\Heroicon::outline('home', 'w-4 h-4') : esc_html($crumb['label']); ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
