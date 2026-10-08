<div class="mx-auto w-full max-w-md" x-data="contabaiLoginForm()">
    <div class="rounded-lg border border-neutral-200 bg-white p-8">
        <h1 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('Login', 'contabai'); ?></h1>
        <form class="space-y-4" x-on:submit.prevent="login()">
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Username', 'contabai'); ?></label>
                <input type="text" x-model="username" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-neutral-700"><?php echo esc_html__('Password', 'contabai'); ?></label>
                <input type="password" x-model="password" required
                       class="flex h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
            </div>
            <button type="submit" x-bind:disabled="loading"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-50">
                <?php echo \Contabai\Heroicon::outline('arrow-left-on-rectangle', 'w-4 h-4'); ?>
                <span x-show="!loading"><?php echo esc_html__('Login', 'contabai'); ?></span>
                <span x-show="loading"><?php echo esc_html__('Logging in...', 'contabai'); ?></span>
            </button>
        </form>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center text-sm">
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_FORGOT_PASSWORD_PAGE_SLUG)); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Forgot password?', 'contabai'); ?></a>
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_REGISTER_PAGE_SLUG)); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Register as Guest', 'contabai'); ?></a>
            <a href="<?php echo esc_url(home_url('/' . CONTABAI_VERIFY_EMAIL_PAGE_SLUG)); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('No verification email?', 'contabai'); ?></a>
            <a href="<?php echo esc_url(home_url()); ?>" class="text-neutral-500 no-underline transition hover:text-neutral-900"><?php echo esc_html__('Back to site', 'contabai'); ?></a>
        </div>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiLoginForm', function () {
        return {
            username: '',
            password: '',
            loading: false,
            login: function () {
                let self = this;
                if (!self.username || !self.password) return;
                self.loading = true;
                fetch('<?php echo esc_url(rest_url('contabai/v1/sanctum/guest/session')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ username: self.username, password: self.password })
                })
                .then(function (r) { return r.json().then(function (data) { return { status: r.status, data: data }; }); })
                .then(function (result) {
                    self.loading = false;
                    if (result.status === 200) {
                        window.location.href = '<?php echo esc_url(home_url('/')); ?>';
                    } else if (result.status === 401) {
                        contabaiToast('<?php echo esc_js(__('Wrong username or password.', 'contabai')); ?>', 'danger');
                    } else if (result.status === 403) {
                        contabaiToast('<?php echo esc_js(__('Please verify your email address first — check your inbox for the link.', 'contabai')); ?>', 'danger');
                    } else if (result.status === 422) {
                        contabaiToast('<?php echo esc_js(__('Enter your username and password.', 'contabai')); ?>', 'danger');
                    } else if (result.status === 429) {
                        contabaiToast(contabaiStatusText(429), 'danger');
                    } else {
                        contabaiToast('<?php echo esc_js(__('Login failed.', 'contabai')); ?>', 'danger');
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
