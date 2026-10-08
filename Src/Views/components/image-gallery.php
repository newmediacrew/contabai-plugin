<?php

if (empty($photos)) {
    return;
}
$count  = count($photos);
$mosaic = $count >= 5;

$typeBadge = '';
if (! empty($typeLabel)) {
    ob_start();
    $badge_variant = 'bg-white/90 text-neutral-800  backdrop-blur';
    $badge_extra   = 'pointer-events-none absolute left-3 top-3 z-10 capitalize';
    $badge_body    = esc_html($typeLabel);
    include __DIR__ . '/badge.php';
    $typeBadge = ob_get_clean();
}
?>
<div class="mb-6"
     x-data='{
        images: <?php echo esc_attr(wp_json_encode($photos)); ?>,
        lbOpen: false, lbIndex: 0,
        lbShow(i) { this.lbIndex = i; this.lbOpen = true; },
        lbNext() { this.lbIndex = (this.lbIndex + 1) % this.images.length; },
        lbPrev() { this.lbIndex = (this.lbIndex - 1 + this.images.length) % this.images.length; }
     }'>

    <!-- Desktop: photo mosaic -->
    <div class="relative hidden md:block">
        <?php if ($mosaic): ?>
            <div class="grid aspect-[5/2] grid-cols-4 grid-rows-2 gap-2 overflow-hidden rounded-2xl">
                <button type="button" x-on:click="lbShow(0)" class="relative col-span-2 row-span-2 overflow-hidden bg-neutral-100">
                    <?php echo $typeBadge; ?>
                    <img src="<?php echo esc_url($photos[0]['hero'] ?? $photos[0]['medium'] ?? ''); ?>"
                         alt="<?php echo esc_attr($photos[0]['alt'] ?? ''); ?>"
                         class="h-full w-full cursor-zoom-in object-cover transition hover:brightness-95">
                </button>
                <?php for ($i = 1; $i <= 4; $i++): $p = $photos[$i]; ?>
                    <button type="button" x-on:click="lbShow(<?php echo $i; ?>)" class="overflow-hidden bg-neutral-100">
                        <img src="<?php echo esc_url($p['card'] ?? $p['medium'] ?? ''); ?>"
                             alt="<?php echo esc_attr($p['alt'] ?? ''); ?>"
                             class="h-full w-full cursor-zoom-in object-cover transition hover:brightness-95">
                    </button>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <button type="button" x-on:click="lbShow(0)" class="relative block aspect-[5/2] w-full overflow-hidden rounded-2xl bg-neutral-100">
                <?php echo $typeBadge; ?>
                <img src="<?php echo esc_url($photos[0]['hero'] ?? $photos[0]['medium'] ?? ''); ?>"
                     alt="<?php echo esc_attr($photos[0]['alt'] ?? ''); ?>"
                     class="h-full w-full cursor-zoom-in object-cover transition hover:brightness-95">
            </button>
        <?php endif; ?>

        <?php if ($count > 1): ?>
            <button type="button" x-on:click="lbShow(0)"
                    class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::solid('squares-2x2', 'w-4 h-4'); ?>
                <?php echo esc_html__('Show all photos', 'contabai'); ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- Mobile: single hero + counter -->
    <div class="relative md:hidden">
        <button type="button" x-on:click="lbShow(0)" class="block aspect-[16/9] w-full overflow-hidden rounded-2xl bg-neutral-100">
            <img src="<?php echo esc_url($photos[0]['hero'] ?? $photos[0]['medium'] ?? ''); ?>"
                 alt="<?php echo esc_attr($photos[0]['alt'] ?? ''); ?>"
                 class="h-full w-full object-cover">
        </button>
        <?php echo $typeBadge; ?>
        <?php if ($count > 1): ?>
            <div class="pointer-events-none absolute bottom-3 right-3 rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white">
                <?php echo esc_html(sprintf(_n('%d photo', '%d photos', $count, 'contabai'), $count)); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- lightbox (teleported to body) -->
    <template x-teleport="body">
        <div x-show="lbOpen" x-cloak x-on:click="lbOpen = false"
             x-on:keydown.escape.window="lbOpen = false"
             x-on:keydown.arrow-right.window="if (lbOpen) lbNext()"
             x-on:keydown.arrow-left.window="if (lbOpen) lbPrev()"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99] flex select-none items-center justify-center bg-black/80">
            <div class="relative inline-flex" x-on:click.stop>
                <img x-bind:src="images[lbIndex] ? (images[lbIndex].hero || images[lbIndex].medium) : ''" x-bind:alt="images[lbIndex] ? images[lbIndex].alt : ''" class="max-h-[90vh] max-w-[92vw] object-contain">
                <button type="button" x-show="images.length > 1" x-on:click.stop="lbPrev()" aria-label="<?php esc_attr_e('Previous', 'contabai'); ?>"
                        class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/60"><?php echo \Contabai\Heroicon::outline('chevron-left', 'w-4 h-4'); ?></button>
                <button type="button" x-show="images.length > 1" x-on:click.stop="lbNext()" aria-label="<?php esc_attr_e('Next', 'contabai'); ?>"
                        class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/60"><?php echo \Contabai\Heroicon::outline('chevron-right', 'w-4 h-4'); ?></button>
            </div>
            <button type="button" x-on:click="lbOpen = false" aria-label="<?php esc_attr_e('Close', 'contabai'); ?>"
                    class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-black/60"><?php echo \Contabai\Heroicon::outline('x-mark', 'w-4 h-4'); ?></button>
        </div>
    </template>
</div>
