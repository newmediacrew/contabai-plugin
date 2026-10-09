<?php

$chevronLeft  = \Contabai\Heroicon::solid('chevron-left', 'w-4 h-4');
$chevronRight = \Contabai\Heroicon::solid('chevron-right', 'w-4 h-4');
?>
<div class="group block">
    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100"
         x-data="{ active: 0, startX: 0 }"
         x-on:touchstart.passive="startX = $event.touches[0].clientX"
         x-on:touchend="active = swipe(listing, active, $event.changedTouches[0].clientX - startX)">
        <div class="flex h-full w-full transition-transform duration-300 ease-out"
             x-bind:style="'transform: translateX(-' + (active * 100) + '%)'">
            <template x-for="(photo, idx) in slides(listing)" x-bind:key="idx">
                <img x-bind:src="photo.card" x-bind:alt="photo.alt" loading="lazy" draggable="false"
                     class="h-full w-full flex-none object-cover">
            </template>
        </div>

        <a x-bind:href="detailUrl(listing)" class="absolute inset-0 z-10" aria-label="<?php echo esc_attr__('View listing', 'contabai'); ?>"></a>

        <?php
        $badge_variant = 'bg-white/90 text-neutral-800  backdrop-blur';
        $badge_extra = 'pointer-events-none absolute left-2.5 top-2.5 z-20 capitalize';
        $badge_attrs = 'x-text="typeLabel(listing)"';
        include __DIR__ . '/badge.php';
        ?>

        <template x-if="slides(listing).length > 1">
            <div>
                <button type="button" x-on:click="active = prevSlide(listing, active)" aria-label="<?php echo esc_attr__('Previous photo', 'contabai'); ?>"
                        class="absolute left-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-neutral-700 opacity-0 transition group-hover:opacity-100 hover:bg-white"><?php echo $chevronLeft; ?></button>
                <button type="button" x-on:click="active = nextSlide(listing, active)" aria-label="<?php echo esc_attr__('Next photo', 'contabai'); ?>"
                        class="absolute right-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-neutral-700 opacity-0 transition group-hover:opacity-100 hover:bg-white"><?php echo $chevronRight; ?></button>
            </div>
        </template>
    </div>

    <a x-bind:href="detailUrl(listing)" class="block pt-2.5 no-underline">
        <p class="contabai-seo-place truncate font-semibold text-neutral-900" x-text="listing.title"></p>
        <p class="truncate text-sm text-neutral-500" x-text="locationLabel(listing)"></p>

        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-neutral-500">
            <?php
            $tt_text = __('Guests', 'contabai');
            $tt_trigger = \Contabai\Heroicon::outline('users', 'w-4 h-4') . '<span x-text="listing.max_guests"></span>';
            include __DIR__ . '/tooltip.php';

            $tt_text = __('Bedrooms', 'contabai');
            $tt_trigger = \Contabai\Heroicon::outline('bed', 'w-4 h-4') . '<span x-text="listing.bedrooms"></span>';
            include __DIR__ . '/tooltip.php';

            $tt_text = __('Bathrooms', 'contabai');
            $tt_trigger = \Contabai\Heroicon::outline('bath', 'w-4 h-4') . '<span x-text="listing.bathrooms"></span>';
            include __DIR__ . '/tooltip.php';

            $tt_text = __('Pool', 'contabai');
            $tt_trigger = \Contabai\Heroicon::outline('pool', 'w-4 h-4') . '<span>1</span>';
            $tt_wrap = 'x-show="(listing.amenities || []).includes(\'pool\')" x-cloak';
            include __DIR__ . '/tooltip.php';
            ?>
        </div>

        <div class="mt-2 flex items-baseline gap-1">
            <span class="text-sm text-neutral-500"><?php echo esc_html__('from', 'contabai'); ?></span>
            <span class="font-bold text-neutral-900" x-text="priceLabel(listing)"></span>
            <span class="text-sm text-neutral-500"><?php echo esc_html__('/ night', 'contabai'); ?></span>
        </div>
    </a>
</div>
