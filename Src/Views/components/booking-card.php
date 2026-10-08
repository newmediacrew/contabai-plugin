<?php

?>
<button type="button" x-on:click="openDetail(b.id)"
        class="group block w-full overflow-hidden rounded-lg border border-neutral-200 bg-white text-left transition">
    <div class="relative aspect-[4/3] bg-neutral-100">
        <img x-bind:src="b.listing.card || b.listing.thumbnail" x-show="b.listing.thumbnail" x-bind:alt="b.listing.image_alt || b.listing.title" loading="lazy"
             class="h-full w-full object-cover transition group-hover:scale-105" x-cloak>
        <?php
        $badge_extra = 'absolute left-3 top-3  backdrop-blur';
        $badge_attrs = 'x-bind:class="statusClass(b.status)" x-text="statusLabel(b.status)"';
        include __DIR__ . '/badge.php';
        ?>
    </div>
    <div class="p-4">
        <h3 class="truncate font-semibold text-neutral-900" x-text="b.listing.title"></h3>
        <p class="truncate text-sm text-neutral-500"><span x-text="b.listing.country_name || b.listing.country"></span> · <span x-text="b.listing.city_name || b.listing.city"></span></p>
        <div class="mt-2 flex items-center gap-1 text-sm text-neutral-500">
            <?php echo \Contabai\Heroicon::outline('calendar-days', 'w-4 h-4'); ?>
            <span x-text="b.arrival"></span> → <span x-text="b.departure"></span> · <span x-text="b.nights"></span> <?php echo esc_html__('nights', 'contabai'); ?>
        </div>
        <div class="mt-2 font-bold text-neutral-900"><span x-text="money(b.due_at_booking_cents, b.currency)"></span></div>
    </div>
</button>
