<div x-data="toastStore()" x-cloak>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition
            class="pointer-events-auto fixed bottom-4 right-4 z-50 max-w-sm rounded-lg border bg-white px-4 py-3 shadow-lg dark:bg-zinc-900"
            :class="{
                'border-emerald-200 text-emerald-900 dark:border-emerald-900 dark:text-emerald-100': toast.type === 'success',
                'border-red-200 text-red-900 dark:border-red-900 dark:text-red-100': toast.type === 'error',
                'border-zinc-200 text-zinc-900 dark:border-zinc-700 dark:text-zinc-100': toast.type === 'info'
            }"
        >
            <p class="text-sm font-medium" x-text="toast.message"></p>
        </div>
    </template>
</div>

<script>
    function toastStore() {
        return {
            toasts: [],
            push(message, type = 'info') {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message, type });
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 4000);
            }
        };
    }

    window.addEventListener('toast', (event) => {
        const root = document.querySelector('[x-data^="toastStore"]');
        if (root && root._x_dataStack) {
            root._x_dataStack[0].push(event.detail.message, event.detail.type ?? 'info');
        }
    });
</script>