<?php
$itemUrl     = \Contabai\Controllers\ListingRewriteController::listing_url($item);
$itemPlace   = implode(' · ', array_filter([(string) ($item['country_name'] ?? ''), (string) ($item['city_name'] ?? ''), (string) ($item['area_name'] ?? '')]));
$itemReviews = (int) ($item['reviews']['count'] ?? 0);
?>
<div class="group block">
    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
        <?php
        $badge_variant = 'bg-white/90 text-neutral-800  backdrop-blur';
        $badge_extra = 'pointer-events-none absolute left-2.5 top-2.5 z-20 capitalize';
        $badge_body = esc_html($propertyTypes[$item['property_type'] ?? ''] ?? ($item['property_type'] ?? ''));
        include __DIR__ . '/badge.php';
        ?>
    </div>
    <a href="<?php echo esc_url($itemUrl); ?>" class="block pt-2.5 no-underline">
        <p class="contabai-seo-place truncate font-semibold text-neutral-900"><?php echo esc_html($item['title'] ?? ''); ?></p>
        <p class="truncate text-sm text-neutral-500"><?php echo esc_html($itemPlace); ?></p>
        <p class="mt-1 flex items-center gap-1 text-sm text-neutral-500">
            <?php if ($itemReviews > 0): ?>
                <span class="text-amber-400"><?php echo \Contabai\Heroicon::solid('star', 'w-4 h-4'); ?></span>
                <span class="font-medium text-neutral-900"><?php echo esc_html(number_format((float) $item['reviews']['average'], 1, '.', '')); ?></span>
                <span class="truncate">· <?php echo esc_html(sprintf(_n('%d review', '%d reviews', $itemReviews, 'contabai'), $itemReviews)); ?></span>
            <?php else: ?>
                <span class="truncate"><?php echo esc_html__('No reviews yet', 'contabai'); ?></span>
            <?php endif; ?>
        </p>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-neutral-500">
            <span class="inline-flex items-center gap-1"><?php echo \Contabai\Heroicon::outline('users', 'w-4 h-4'); ?><?php echo (int) ($item['max_guests'] ?? 0); ?></span>
            <span class="inline-flex items-center gap-1"><?php echo \Contabai\Heroicon::outline('bed', 'w-4 h-4'); ?><?php echo (int) ($item['bedrooms'] ?? 0); ?></span>
            <span class="inline-flex items-center gap-1"><?php echo \Contabai\Heroicon::outline('bath', 'w-4 h-4'); ?><?php echo (int) ($item['bathrooms'] ?? 0); ?></span>
            <?php if (in_array('pool', $item['amenities'] ?? [], true)): ?>
                <span class="inline-flex items-center gap-1"><?php echo \Contabai\Heroicon::outline('pool', 'w-4 h-4'); ?>1</span>
            <?php endif; ?>
        </div>
        <div class="mt-2 flex items-baseline gap-1">
            <span class="text-sm text-neutral-500"><?php echo esc_html__('from', 'contabai'); ?></span>
            <span class="font-bold text-neutral-900"><?php echo esc_html(($item['currency'] ?? 'EUR') . ' ' . number_format(((int) ($item['from_price_cents'] ?? 0)) / 100, 2, '.', '')); ?></span>
            <span class="text-sm text-neutral-500"><?php echo esc_html__('/ night', 'contabai'); ?></span>
        </div>
    </a>
</div>
