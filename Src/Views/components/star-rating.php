<?php

$sr_rating = max(0, min(5, (int) round((float) ($sr_rating ?? 0))));
$sr_size   = $sr_size ?? 'w-5 h-5';
?>
<span class="inline-flex items-center gap-0.5" role="img" aria-label="<?php echo esc_attr(sprintf(__('Rated %d out of 5', 'contabai'), $sr_rating)); ?>">
    <?php for ($i = 1; $i <= 5; $i++): ?>
        <?php echo \Contabai\Heroicon::solid('star', $sr_size . ' ' . ($i <= $sr_rating ? 'text-amber-400' : 'text-neutral-300')); ?>
    <?php endfor; ?>
</span>
<?php
$sr_rating = null;
$sr_size   = null;
