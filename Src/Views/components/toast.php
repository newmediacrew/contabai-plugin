<?php

$toastIcons = wp_json_encode([
    'success' => \Contabai\Heroicon::solid('check-circle', 'w-4 h-4 text-green-500'),
    'danger'  => \Contabai\Heroicon::solid('x-circle', 'w-4 h-4 text-red-500'),
    'warning' => \Contabai\Heroicon::solid('exclamation-triangle', 'w-4 h-4 text-orange-400'),
    'info'    => \Contabai\Heroicon::solid('information-circle', 'w-4 h-4 text-blue-500'),
]);
?>
<div x-data="contabaiToastContainer()" x-on:show-toast.window="addToast($event.detail)"
     class="pointer-events-none fixed left-1/2 top-4 z-[100] flex w-full max-w-sm -translate-x-1/2 flex-col gap-2 px-4 sm:px-0">
    <template x-for="toast in toasts" x-bind:key="toast.id">
        <div x-show="toast.visible"
             class="pointer-events-auto flex w-full items-start gap-3 rounded-lg border border-neutral-100 bg-white p-4"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-3">
            <span class="mt-0.5 flex-none" x-html="iconFor(toast.type)"></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-neutral-900" x-text="toast.message"></p>
                <p x-show="toast.description" class="mt-1 text-sm text-neutral-500" x-text="toast.description"></p>
            </div>
            <button type="button" x-on:click="removeToast(toast.id)" class="-mr-1 -mt-1 flex-none rounded p-1 text-neutral-400 transition hover:text-neutral-600"><?php echo \Contabai\Heroicon::outline('x-mark', 'w-4 h-4'); ?></button>
        </div>
    </template>
</div>
<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('contabaiToastContainer', function () {
        return {
            toasts: [],
            counter: 0,
            icons: <?php echo $toastIcons; ?>,
            iconFor: function (type) {
                return this.icons[type] || this.icons.info;
            },
            addToast: function (detail) {
                let self = this;
                let id = ++self.counter;
                self.toasts.push({
                    id: id,
                    message: detail.message,
                    description: detail.description || '',
                    type: detail.type || 'info',
                    visible: true
                });
                setTimeout(function () { self.removeToast(id); }, 5000);
            },
            removeToast: function (id) {
                let self = this;
                let toast = self.toasts.find(function (t) { return t.id === id; });
                if (toast) { toast.visible = false; }
                setTimeout(function () {
                    self.toasts = self.toasts.filter(function (t) { return t.id !== id; });
                }, 300);
            }
        };
    });
});

function contabaiToast(message, type, description) {
    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: message, type: type || 'info', description: description || '' } }));
}

function contabaiStatusText(status, fallback) {
    if (status === 401) return '<?php echo esc_js(__('Your session has expired. Please log in again.', 'contabai')); ?>';
    if (status === 429) return '<?php echo esc_js(__('Too many attempts. Please wait a minute and try again.', 'contabai')); ?>';
    return fallback || '<?php echo esc_js(__('Something went wrong.', 'contabai')); ?>';
}

const contabaiFieldText = <?php echo wp_json_encode([
    'username' => __('That username is taken or not allowed. Use letters, numbers, dashes and underscores only.', 'contabai'),
    'email'    => __('Enter a valid email address.', 'contabai'),
    'password' => __('Use at least 8 characters including a number, avoid known leaked passwords, and type the same password twice.', 'contabai'),
    'nickname' => __('That nickname is already taken. Choose another one.', 'contabai'),
    'country'  => __('Choose a country from the list.', 'contabai'),
    'avatar'   => __('Upload a JPG, PNG or WebP image of at most 5 MB.', 'contabai'),
    'body'     => __('Your message is empty or longer than 2,000 characters.', 'contabai'),
]); ?>;

function contabaiFieldErrors(errors, overrides, fallback) {
    let out = [];
    Object.keys(errors || {}).forEach(function (key) {
        let text = (overrides && overrides[key]) || contabaiFieldText[key] || fallback || contabaiStatusText(0);
        if (out.indexOf(text) === -1) { out.push(text); }
    });
    return out;
}
</script>
