<?php

$pg_neutral = $pg_neutral ?? false;
$pg_active  = $pg_neutral ? 'bg-neutral-900 text-white' : 'bg-[var(--theme-color,#ff5400)] text-white';
$pg_hover   = $pg_neutral ? 'hover:bg-neutral-100 hover:text-neutral-900' : 'hover:bg-[var(--theme-color,#ff5400)] hover:text-white';
?>
<div x-show="lastPage > 1" x-cloak class="mt-10 flex w-full flex-col items-center gap-3 border-t border-neutral-200 px-3 pt-4 text-sm sm:flex-row sm:justify-between">
    <p class="pl-2 text-neutral-600"><?php
        printf(
            esc_html__('Showing %1$s to %2$s of %3$s results', 'contabai'),
            '<span class="font-medium" x-text="Math.min((currentPage - 1) * perPage + 1, total)"></span>',
            '<span class="font-medium" x-text="Math.min(currentPage * perPage, total)"></span>',
            '<span class="font-medium" x-text="total"></span>'
        );
    ?></p>
    <nav>
        <ul class="flex h-11 items-center divide-x divide-neutral-200 overflow-hidden rounded-lg border border-neutral-200 bg-white text-sm font-medium tabular-nums text-neutral-600 !m-0 !list-none !p-0">
            <li class="h-full">
                <button type="button" x-on:click="goTo(currentPage - 1)" x-bind:disabled="currentPage <= 1"
                        class="inline-flex h-full items-center px-4 transition <?php echo $pg_hover; ?> disabled:pointer-events-none disabled:opacity-40">
                    <?php echo esc_html__('Previous', 'contabai'); ?>
                </button>
            </li>
            <template x-for="p in pages()" x-bind:key="p.key">
                <li class="hidden h-full md:block">
                    <template x-if="p.ellipsis">
                        <div class="inline-flex h-full min-w-11 items-center justify-center px-2 text-neutral-400">…</div>
                    </template>
                    <template x-if="!p.ellipsis">
                        <button type="button" x-on:click="goTo(p.n)"
                                class="inline-flex h-full min-w-11 items-center justify-center px-3 transition"
                                x-bind:class="p.n === currentPage ? '<?php echo esc_attr($pg_active); ?>' : '<?php echo esc_attr($pg_hover); ?>'"
                                x-text="p.n"></button>
                    </template>
                </li>
            </template>
            <li class="h-full">
                <button type="button" x-on:click="goTo(currentPage + 1)" x-bind:disabled="currentPage >= lastPage"
                        class="inline-flex h-full items-center px-4 transition <?php echo $pg_hover; ?> disabled:pointer-events-none disabled:opacity-40">
                    <?php echo esc_html__('Next', 'contabai'); ?>
                </button>
            </li>
        </ul>
    </nav>
</div>
