<div class="mx-auto w-full max-w-md">
    <div class="rounded-lg border border-neutral-200 bg-white p-8">
        <h1 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('My hub', 'contabai'); ?></h1>
        <div class="space-y-2">
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_PROFILE_PAGE_SLUG)); ?>"
               class="flex items-center gap-3 rounded-md border border-neutral-300 px-4 py-3 text-sm font-medium text-neutral-800 no-underline transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::outline('user-circle', 'w-4 h-4'); ?>
                <?php echo esc_html__('My account', 'contabai'); ?>
            </a>

            <a href="<?php echo esc_url(home_url('/' . CONTABAI_BOOKINGS_PAGE_SLUG)); ?>"
               class="flex items-center gap-3 rounded-md border border-neutral-300 px-4 py-3 text-sm font-medium text-neutral-800 no-underline transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::outline('ticket', 'w-4 h-4'); ?>
                <?php echo esc_html__('My bookings', 'contabai'); ?>
            </a>

            <a href="<?php echo esc_url(home_url('/' . CONTABAI_CHAT_PAGE_SLUG)); ?>"
               class="flex items-center gap-3 rounded-md border border-neutral-300 px-4 py-3 text-sm font-medium text-neutral-800 no-underline transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::outline('chat-bubble-left-right', 'w-4 h-4'); ?>
                <?php echo esc_html__('My chats', 'contabai'); ?>
                <span class="js-chat-unread contabai-chat-badge ml-auto inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold text-white"></span>
            </a>

            <button type="button" onclick="contabaiLogout(event)"
                    class="flex w-full items-center gap-3 rounded-md border border-neutral-300 px-4 py-3 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50">
                <?php echo \Contabai\Heroicon::outline('arrow-right-on-rectangle', 'w-4 h-4'); ?>
                <?php echo esc_html__('Log out', 'contabai'); ?>
            </button>
        </div>
        <div class="mt-6 text-center text-sm">
            <a href="<?php echo esc_url(home_url()); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to site', 'contabai'); ?></a>
        </div>
    </div>
</div>
<script>
// The blank Session template has no theme header, so define the logout handler here.
function contabaiLogout(e) {
    e.preventDefault();
    fetch('<?php echo esc_url(rest_url('contabai/v1/sanctum/guest/session')); ?>', { method: 'DELETE', credentials: 'same-origin' })
        .then(function () { window.location.href = '<?php echo esc_url(home_url()); ?>'; })
        .catch(function () { window.location.href = '<?php echo esc_url(home_url()); ?>'; });
}
</script>
