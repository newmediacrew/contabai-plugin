<?php

$sel_onchange = $sel_onchange ?? '';
?>
<div x-data="{ selOpen: false, selActive: -1 }"
     x-on:keydown.escape.stop="selOpen = false"
     x-on:keydown.arrow-down.prevent="selOpen ? (selActive = Math.min(selActive + 1, (<?php echo $sel_items; ?>).length - 1)) : (selOpen = true)"
     x-on:keydown.arrow-up.prevent="selOpen ? (selActive = Math.max(selActive - 1, 0)) : (selOpen = true)"
     x-on:keydown.enter.prevent="if (selOpen && selActive >= 0) { let __i = (<?php echo $sel_items; ?>)[selActive]; if (__i) { <?php echo $sel_value; ?> = __i.value; <?php echo $sel_onchange; ?>; } selOpen = false; }"
     class="relative">
    <button type="button" x-ref="selBtn" x-bind:aria-expanded="selOpen"
            x-on:click="selOpen = !selOpen; selActive = (<?php echo $sel_items; ?>).findIndex(i => i.value === (<?php echo $sel_value; ?>))"
            class="relative flex h-10 w-full items-center justify-between rounded-md border border-neutral-300 bg-white py-2 pl-3 pr-9 text-left text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
        <span class="truncate" x-text="((<?php echo $sel_items; ?>).find(i => i.value === (<?php echo $sel_value; ?>)) || {}).title || '<?php echo esc_js($sel_placeholder); ?>'"></span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-neutral-400"><?php echo \Contabai\Heroicon::outline('chevron-up-down', 'w-4 h-4'); ?></span>
    </button>
    <ul x-show="selOpen" x-cloak x-on:click.away="selOpen = false"
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-md border border-neutral-200 bg-white py-1 text-sm !mb-0 !list-none !pl-0">
        <template x-for="(item, idx) in (<?php echo $sel_items; ?>)" x-bind:key="item.value">
            <li x-on:click="<?php echo $sel_value; ?> = item.value; <?php echo $sel_onchange; ?>; selOpen = false; $refs.selBtn.focus();"
                x-on:mousemove="selActive = idx"
                x-bind:class="selActive === idx ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-700'"
                class="relative flex cursor-default select-none items-center py-2 pl-8 pr-3">
                <span class="absolute left-2 flex items-center" x-show="(<?php echo $sel_value; ?>) === item.value"><?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4 text-neutral-500'); ?></span>
                <span class="block truncate" x-text="item.title"></span>
            </li>
        </template>
    </ul>
</div>
