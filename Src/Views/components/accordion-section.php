<?php

$acc_key   = $acc_key ?? '';
$acc_title = $acc_title ?? '';
$acc_body  = $acc_body ?? '';
$acc_k     = esc_js($acc_key);
?>
<section class="rounded-lg border border-neutral-200 bg-white"
         x-bind:class="open === '<?php echo $acc_k; ?>' ? '' : 'overflow-hidden'">
    <button type="button" x-on:click="toggle('<?php echo $acc_k; ?>')" class="flex w-full items-center justify-between px-5 py-4 text-left">
        <h3 class="text-base font-semibold text-neutral-900"><?php echo esc_html($acc_title); ?></h3>
        <span class="text-neutral-400 transition" x-bind:class="open === '<?php echo $acc_k; ?>' ? 'rotate-180' : ''"><?php echo \Contabai\Heroicon::outline('chevron-down', 'w-4 h-4'); ?></span>
    </button>
    <div x-show="open === '<?php echo $acc_k; ?>'" x-collapse>
        <div class="border-t border-neutral-200 px-5 py-5"><?php echo $acc_body; ?></div>
    </div>
</section>
<?php $acc_key = $acc_title = $acc_body = null; ?>
