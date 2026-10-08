<div class="mx-auto w-full max-w-md" x-data="contabaiForgotPasswordForm()">
    <div class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-lg">
        <h1 class="mb-2 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Forgot password', 'contabai'); ?></h1>
        <p class="mb-6 text-sm text-neutral-500"><?php echo esc_html__('Enter your username and we will email you a reset link.', 'contabai'); ?></p>
        <form class="space-y-4" x-on:submit.prevent="submit()">
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Username', 'contabai'); ?></label>
                <input type="text" x-model="username" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <button type="submit" x-bind:disabled="loading || cooldown > 0"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-50">
                <?php echo \Contabai\Heroicon::outline('paper-airplane', 'w-4 h-4'); ?>
                <span x-show="!loading && cooldown === 0"><?php echo esc_html__('Send reset link', 'contabai'); ?></span>
                <span x-show="loading"><?php echo esc_html__('Sending...', 'contabai'); ?></span>
                <span x-show="!loading && cooldown > 0"><?php echo esc_html__('Resend in', 'contabai'); ?> <span x-text="cooldown"></span>s</span>
            </button>
        </form>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center text-sm">
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_LOGIN_PAGE_SLUG)); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to login', 'contabai'); ?></a>
            <a href="<?php echo esc_url(home_url()); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to site', 'contabai'); ?></a>
        </div>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiForgotPasswordForm', function () {
        return {
            username: '',
            loading: false,
            cooldown: 0,
            startCooldown: function () {
                let self = this;
                self.cooldown = 60;
                let id = setInterval(function () {
                    self.cooldown = self.cooldown - 1;
                    if (self.cooldown <= 0) { clearInterval(id); }
                }, 1000);
            },
            submit: function () {
                let self = this;
                if (!self.username || self.cooldown > 0) return;
                self.loading = true;
                fetch('<?php echo esc_url(rest_url('contabai/v1/sanctum/guest/forgot-password')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ username: self.username })
                })
                .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, status: r.status, data: data }; }); })
                .then(function (result) {
                    self.loading = false;
                    if (result.ok) {
                        contabaiToast('<?php echo esc_js(__('If an account exists, a reset link has been sent.', 'contabai')); ?>', 'success');
                        self.startCooldown();
                    } else if (result.status === 422) {
                        contabaiToast('<?php echo esc_js(__('Enter your username.', 'contabai')); ?>', 'danger');
                    } else {
                        contabaiToast(contabaiStatusText(result.status), 'danger');
                    }
                })
                .catch(function () {
                    self.loading = false;
                    contabaiToast('<?php echo esc_js(__('Connection error.', 'contabai')); ?>', 'danger');
                });
            }
        };
    });
});
</script>
