<div class="mx-auto w-full max-w-md" x-data="contabaiRegisterForm()">
    <div class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-lg">
        <h1 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Create account', 'contabai'); ?></h1>
        <form class="space-y-4" x-on:submit.prevent="submit()">
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Username', 'contabai'); ?></label>
                <input type="text" x-model="username" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Email', 'contabai'); ?></label>
                <input type="email" x-model="email" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Password', 'contabai'); ?></label>
                <input type="password" x-model="password" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Confirm password', 'contabai'); ?></label>
                <input type="password" x-model="passwordConfirmation" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Language', 'contabai'); ?></label>
                <?php
                $sel_value = 'language';
                $sel_items = "Object.entries(languageOptions).map(e => ({ value: e[0], title: e[1] }))";
                $sel_placeholder = __('Select language', 'contabai');
                $sel_onchange = '';
                include __DIR__ . '/../components/select.php';
                ?>
            </div>
            <button type="submit" x-bind:disabled="loading"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-50">
                <?php echo \Contabai\Heroicon::outline('user-plus', 'w-4 h-4'); ?>
                <span x-show="!loading"><?php echo esc_html__('Create account', 'contabai'); ?></span>
                <span x-show="loading"><?php echo esc_html__('Creating account...', 'contabai'); ?></span>
            </button>
        </form>
        <div class="mt-6 text-center text-sm text-neutral-500">
            <?php echo esc_html__('Already have an account?', 'contabai'); ?>
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_LOGIN_PAGE_SLUG)); ?>" class="font-medium text-neutral-900 no-underline hover:underline"><?php echo esc_html__('Log in', 'contabai'); ?></a>
        </div>
        <div class="mt-2 text-center text-sm">
            <a href="<?php echo esc_url(home_url()); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to site', 'contabai'); ?></a>
        </div>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiRegisterForm', function () {
        return {
            username: '',
            email: '',
            password: '',
            passwordConfirmation: '',
            language: '',
            languageOptions: <?php echo wp_json_encode($languages ?? []); ?>,
            loading: false,
            init: function () {
                let keys = Object.keys(this.languageOptions);
                if (! this.language && keys.length) { this.language = keys[0]; }
            },
            submit: function () {
                let self = this;
                if (!self.username || !self.email || !self.password) return;
                self.loading = true;
                fetch('<?php echo esc_url(rest_url('contabai/v1/sanctum/guest/register')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        username: self.username,
                        email: self.email,
                        password: self.password,
                        password_confirmation: self.passwordConfirmation,
                        language: self.language
                    })
                })
                .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, status: r.status, data: data }; }); })
                .then(function (result) {
                    self.loading = false;
                    if (result.ok) {
                        window.location.href = '<?php echo esc_url(home_url('/' . CONTABAI_VERIFY_EMAIL_PAGE_SLUG)); ?>?email=' + encodeURIComponent(self.email) + '&username=' + encodeURIComponent(self.username);
                    } else if (result.status === 422 && result.data && result.data.errors) {
                        contabaiFieldErrors(result.data.errors, null, '<?php echo esc_js(__('Registration failed.', 'contabai')); ?>').forEach(function (msg) { contabaiToast(msg, 'danger'); });
                    } else {
                        contabaiToast(contabaiStatusText(result.status, '<?php echo esc_js(__('Registration failed.', 'contabai')); ?>'), 'danger');
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
