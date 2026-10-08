<div x-data="contabaiAccount()" class="mx-auto max-w-2xl px-4 py-8">
    <?php echo \Contabai\View::render('components.breadcrumb', ['crumbs' => [
        ['label' => __('Hub', 'contabai'), 'url' => home_url('/' . CONTABAI_ACCOUNT_PAGE_SLUG)],
        ['label' => __('My account', 'contabai'), 'url' => null],
    ]]); ?>
    <h2 class="mb-6 text-2xl font-bold text-neutral-900"><?php echo esc_html__('My account', 'contabai'); ?></h2>
    <div x-show="loading" class="py-16 text-center text-sm text-neutral-500"><?php echo esc_html__('Loading…', 'contabai'); ?></div>

    <div x-show="loaded" x-cloak class="space-y-3">
        <!-- Profile -->
        <?php $acc_key = 'profile'; $acc_title = __('Profile', 'contabai'); ob_start(); ?>
                    <form x-on:submit.prevent="saveProfile()" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Username', 'contabai'); ?></label>
                            <input type="text" x-model="username" readonly class="h-10 w-full rounded-md border border-neutral-300 bg-neutral-50 px-3 py-2 text-sm text-neutral-500">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Nickname', 'contabai'); ?></label>
                                <input type="text" x-model="nickname" required class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Language', 'contabai'); ?></label>
                                <?php
                                $sel_value = 'language';
                                $sel_items = "Object.entries(languageOptions).map(e => ({ value: e[0], title: e[1] }))";
                                $sel_placeholder = __('Select language', 'contabai');
                                $sel_onchange = '';
                                include __DIR__ . '/../components/select.php';
                                ?>
                            </div>
                        </div>
                        <button type="submit" x-bind:disabled="saving === 'profile'" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-60">
                            <?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?>
                            <span x-show="saving !== 'profile'"><?php echo esc_html__('Update', 'contabai'); ?></span>
                            <span x-show="saving === 'profile'"><?php echo esc_html__('Updating…', 'contabai'); ?></span>
                        </button>
                    </form>
        <?php $acc_body = ob_get_clean(); include __DIR__ . '/../components/accordion-section.php'; ?>

        <!-- Contact details -->
        <?php $acc_key = 'contact'; $acc_title = __('Contact details', 'contabai'); ob_start(); ?>
                    <form x-on:submit.prevent="saveContact()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('First name', 'contabai'); ?></label>
                                <input type="text" x-model="first_name" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Last name', 'contabai'); ?></label>
                                <input type="text" x-model="last_name" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Address', 'contabai'); ?></label>
                            <input type="text" x-model="address" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Postcode', 'contabai'); ?></label>
                                <input type="text" x-model="postcode" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('City', 'contabai'); ?></label>
                                <input type="text" x-model="city" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Country', 'contabai'); ?></label>
                                <?php
                                $sel_value = 'country';
                                $sel_items = 'countryOptions';
                                $sel_placeholder = __('Select country', 'contabai');
                                $sel_onchange = '';
                                include __DIR__ . '/../components/select.php';
                                ?>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Phone', 'contabai'); ?></label>
                            <input type="text" x-model="phone" class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                        </div>
                        <button type="submit" x-bind:disabled="saving === 'contact'" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-60">
                            <?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?>
                            <span x-show="saving !== 'contact'"><?php echo esc_html__('Update', 'contabai'); ?></span>
                            <span x-show="saving === 'contact'"><?php echo esc_html__('Updating…', 'contabai'); ?></span>
                        </button>
                    </form>
        <?php $acc_body = ob_get_clean(); include __DIR__ . '/../components/accordion-section.php'; ?>

        <!-- Avatar -->
        <?php $acc_key = 'avatar'; $acc_title = __('Avatar', 'contabai'); ob_start(); ?>
                    <div class="flex items-center gap-5">
                        <img x-bind:src="avatarUrl" x-show="avatarUrl" alt="" x-cloak class="h-20 w-20 rounded-full object-cover">
                        <div x-show="!avatarUrl" class="flex h-20 w-20 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                            <?php echo \Contabai\Heroicon::outline('users', 'w-4 h-4'); ?>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-neutral-300 bg-white px-4 py-2 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50">
                                <?php echo \Contabai\Heroicon::outline('arrow-up-tray', 'w-4 h-4'); ?>
                                <span x-show="saving !== 'avatar'"><?php echo esc_html__('Upload avatar', 'contabai'); ?></span>
                                <span x-show="saving === 'avatar'"><?php echo esc_html__('Uploading…', 'contabai'); ?></span>
                                <input type="file" accept="image/webp,image/jpeg,image/png" x-bind:disabled="saving === 'avatar'" x-on:change="uploadAvatar($event)" class="hidden">
                            </label>
                            <button type="button" x-show="hasCustomAvatar()" x-bind:disabled="saving === 'avatar'" x-on:click="deleteAvatar()" x-cloak class="inline-flex items-center gap-2 rounded-md border border-neutral-300 px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50 disabled:opacity-60">
                                <?php echo \Contabai\Heroicon::outline('trash', 'w-4 h-4'); ?>
                                <?php echo esc_html__('Remove avatar', 'contabai'); ?>
                            </button>
                        </div>
                    </div>
        <?php $acc_body = ob_get_clean(); include __DIR__ . '/../components/accordion-section.php'; ?>

        <!-- Email -->
        <?php $acc_key = 'email'; $acc_title = __('Email', 'contabai'); ob_start(); ?>
                    <form x-on:submit.prevent="saveEmail()" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Email', 'contabai'); ?></label>
                            <input type="email" x-model="email" required class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                        </div>
                        <button type="submit" x-bind:disabled="saving === 'email'" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-60">
                            <?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?>
                            <span x-show="saving !== 'email'"><?php echo esc_html__('Update', 'contabai'); ?></span>
                            <span x-show="saving === 'email'"><?php echo esc_html__('Updating…', 'contabai'); ?></span>
                        </button>
                    </form>
        <?php $acc_body = ob_get_clean(); include __DIR__ . '/../components/accordion-section.php'; ?>

        <!-- Password -->
        <?php $acc_key = 'password'; $acc_title = __('Change password', 'contabai'); ob_start(); ?>
                    <form x-on:submit.prevent="savePassword()" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('New password', 'contabai'); ?></label>
                                <input type="password" x-model="password" required class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-neutral-500"><?php echo esc_html__('Confirm new password', 'contabai'); ?></label>
                                <input type="password" x-model="passwordConfirmation" required class="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-neutral-400 focus:ring-offset-2">
                            </div>
                        </div>
                        <button type="submit" x-bind:disabled="saving === 'password'" class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-neutral-300 bg-white px-5 text-sm font-medium text-neutral-800 transition hover:bg-neutral-50 disabled:opacity-60">
                            <?php echo \Contabai\Heroicon::outline('check', 'w-4 h-4'); ?>
                            <span x-show="saving !== 'password'"><?php echo esc_html__('Update', 'contabai'); ?></span>
                            <span x-show="saving === 'password'"><?php echo esc_html__('Updating…', 'contabai'); ?></span>
                        </button>
                    </form>
        <?php $acc_body = ob_get_clean(); include __DIR__ . '/../components/accordion-section.php'; ?>
    </div>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiAccount', function () {
        return {
            endpoints: <?php echo wp_json_encode([
                'read' => rest_url('contabai/v1/sanctum/guest/session'),
                'profile' => rest_url('contabai/v1/sanctum/guest/profile'),
                'contact' => rest_url('contabai/v1/sanctum/guest/profile/contact-details'),
                'email' => rest_url('contabai/v1/sanctum/guest/profile/email'),
                'password' => rest_url('contabai/v1/sanctum/guest/profile/password'),
                'avatar' => rest_url('contabai/v1/sanctum/guest/profile/avatar'),
            ]); ?>,
            loaded: false,
            loading: false,
            saving: '',
            open: '',
            toggle: function (section) {
                this.open = this.open === section ? '' : section;
            },
            username: '',
            nickname: '',
            language: '',
            languageOptions: <?php echo wp_json_encode($languages ?? []); ?>,
            countryOptions: <?php echo wp_json_encode(array_merge([['value' => '', 'title' => __('Select country', 'contabai')]], $countryOptions ?? [])); ?>,
            contactFieldText: <?php echo wp_json_encode(array_fill_keys(['first_name', 'last_name', 'address', 'postcode', 'city', 'phone'], __('Each field can be at most 255 characters.', 'contabai'))); ?>,
            first_name: '',
            last_name: '',
            address: '',
            postcode: '',
            city: '',
            country: '',
            phone: '',
            email: '',
            password: '',
            passwordConfirmation: '',
            avatarUrl: '',
            init: function () {
                this.load();
            },
            isCountry: function (code) {
                return this.countryOptions.some(function (option) { return option.value !== '' && option.value === code; });
            },
            hasCustomAvatar: function () {
                return this.avatarUrl !== '' && this.avatarUrl.indexOf('/avatars/default/') === -1;
            },
            load: function () {
                let self = this;
                self.loading = true;
                let profileReq = fetch(self.endpoints.read, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { if (! r.ok) { let e = new Error('http'); e.status = r.status; throw e; } return r.json(); })
                    .then(function (res) {
                        let user = (res && res.data) || {};
                        self.username = user.username || '';
                        self.nickname = user.nickname || '';
                        self.language = user.language || '';
                        self.first_name = user.first_name || '';
                        self.last_name = user.last_name || '';
                        self.address = user.address || '';
                        self.postcode = user.postcode || '';
                        self.city = user.city || '';
                        self.country = self.isCountry(user.country) ? user.country : '';
                        self.phone = user.phone || '';
                        self.email = user.email || '';
                        self.avatarUrl = (user.avatar && (user.avatar['256x256'] || user.avatar['original'])) || '';
                    });
                profileReq
                    .then(function () { self.loaded = true; self.loading = false; })
                    .catch(function (e) { self.loading = false; contabaiToast(contabaiStatusText(e.status, '<?php echo esc_js(__('Could not load your account.', 'contabai')); ?>'), 'danger'); });
            },
            patch: function (endpoint, data, section) {
                let self = this;
                self.saving = section;
                fetch(endpoint, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(data)
                })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; }); })
                .then(function (result) {
                    self.saving = '';
                    if (result.ok) {
                        contabaiToast('<?php echo esc_js(__('Saved.', 'contabai')); ?>', 'success');
                        if (section === 'password') { self.password = ''; self.passwordConfirmation = ''; }
                        if (section === 'profile') { self.nickname = self.nickname.toLowerCase(); }
                    } else if (result.status === 422 && result.data && result.data.errors) {
                        contabaiFieldErrors(result.data.errors, self.contactFieldText).forEach(function (msg) { contabaiToast(msg, 'danger'); });
                    } else {
                        contabaiToast(contabaiStatusText(result.status), 'danger');
                    }
                })
                .catch(function () {
                    self.saving = '';
                    contabaiToast('<?php echo esc_js(__('Connection error.', 'contabai')); ?>', 'danger');
                });
            },
            saveProfile: function () {
                this.patch(this.endpoints.profile, { nickname: this.nickname, language: this.language }, 'profile');
            },
            saveContact: function () {
                this.patch(this.endpoints.contact, {
                    first_name: this.first_name, last_name: this.last_name, address: this.address,
                    postcode: this.postcode, city: this.city, country: this.country, phone: this.phone
                }, 'contact');
            },
            saveEmail: function () {
                this.patch(this.endpoints.email, { email: this.email }, 'email');
            },
            savePassword: function () {
                this.patch(this.endpoints.password, { password: this.password, password_confirmation: this.passwordConfirmation }, 'password');
            },
            uploadAvatar: function (event) {
                let self = this;
                let file = event.target.files[0];
                if (! file) return;
                let formData = new FormData();
                formData.append('avatar', file);
                self.saving = 'avatar';
                fetch(self.endpoints.avatar, { method: 'POST', credentials: 'same-origin', body: formData })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; }); })
                .then(function (result) {
                    self.saving = '';
                    if (result.ok) {
                        contabaiToast('<?php echo esc_js(__('Saved.', 'contabai')); ?>', 'success');
                        window.location.reload();
                    } else if (result.status === 422 && result.data && result.data.errors) {
                        contabaiFieldErrors(result.data.errors).forEach(function (msg) { contabaiToast(msg, 'danger'); });
                    } else {
                        contabaiToast(contabaiStatusText(result.status), 'danger');
                    }
                })
                .catch(function () {
                    self.saving = '';
                    contabaiToast('<?php echo esc_js(__('Connection error.', 'contabai')); ?>', 'danger');
                });
            },
            deleteAvatar: function () {
                let self = this;
                self.saving = 'avatar';
                fetch(self.endpoints.avatar, { method: 'DELETE', credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; }); })
                .then(function (result) {
                    self.saving = '';
                    if (result.ok) {
                        contabaiToast('<?php echo esc_js(__('Saved.', 'contabai')); ?>', 'success');
                        window.location.reload();
                    } else {
                        contabaiToast(contabaiStatusText(result.status), 'danger');
                    }
                })
                .catch(function () {
                    self.saving = '';
                    contabaiToast('<?php echo esc_js(__('Connection error.', 'contabai')); ?>', 'danger');
                });
            }
        };
    });
});
</script>
