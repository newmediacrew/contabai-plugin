<?php

$badge_body    = $badge_body ?? '';
$badge_variant = $badge_variant ?? '';
$badge_extra   = $badge_extra ?? '';
$badge_attrs   = $badge_attrs ?? '';
$badge_tag     = (($badge_tag ?? 'span') === 'a') ? 'a' : 'span';
$badge_class   = trim('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ' . $badge_variant . ' ' . $badge_extra);
?>
<<?php echo $badge_tag; ?> class="<?php echo esc_attr($badge_class); ?>"<?php echo $badge_attrs ? ' ' . $badge_attrs : ''; ?>><?php echo $badge_body; ?></<?php echo $badge_tag; ?>>
<?php $badge_body = $badge_variant = $badge_extra = $badge_attrs = $badge_tag = null; ?>
