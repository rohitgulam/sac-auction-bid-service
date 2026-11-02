const previousState = new Map();

function registerCountdown() {
    window.Alpine.data('auctionCountdown', (deadline) => ({
        deadline: Date.parse(deadline),
        remainingSeconds: 0,
        intervalId: null,

        init() {
            this.update();
            this.intervalId = window.setInterval(() => this.update(), 1000);
        },

        destroy() {
            window.clearInterval(this.intervalId);
        },

        update() {
            this.remainingSeconds = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
        },

        get label() {
            if (this.remainingSeconds === 0) return 'Closed';

            const days = Math.floor(this.remainingSeconds / 86400);
            const hours = Math.floor((this.remainingSeconds % 86400) / 3600);
            const minutes = Math.floor((this.remainingSeconds % 3600) / 60);
            const seconds = this.remainingSeconds % 60;

            return [
                days > 0 ? `${days}d` : null,
                days > 0 || hours > 0 ? `${hours}h` : null,
                `${minutes}m`,
                `${String(seconds).padStart(2, '0')}s`,
            ].filter(Boolean).join(' ');
        },
    }));
}

function registerAuctionAnimations() {
    window.Livewire.hook('morph', ({ el, component }) => {
        const values = new Map();
        const positions = new Map();

        el.querySelectorAll('[data-live-key]').forEach((value) => {
            values.set(value.dataset.liveKey, value.dataset.liveValue);
        });
        el.querySelectorAll('[data-auction-card], [data-standing-row]').forEach((row) => {
            const key = row.dataset.auctionCard ? `card:${row.dataset.auctionCard}` : `standing:${row.dataset.standingRow}`;
            positions.set(key, row.getBoundingClientRect());
        });

        previousState.set(component.id, { values, positions });
    });

    window.Livewire.hook('morphed', ({ el, component }) => {
        const before = previousState.get(component.id);
        previousState.delete(component.id);

        if (!before) return;

        el.querySelectorAll('[data-live-key]').forEach((value) => {
            const oldValue = before.values.get(value.dataset.liveKey);

            if (oldValue === undefined || oldValue === value.dataset.liveValue) return;

            value.classList.remove('auction-value-changed');
            void value.offsetWidth;
            value.classList.add('auction-value-changed');
            value.addEventListener('animationend', () => value.classList.remove('auction-value-changed'), { once: true });
        });

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        el.querySelectorAll('[data-auction-card], [data-standing-row]').forEach((row) => {
            const key = row.dataset.auctionCard ? `card:${row.dataset.auctionCard}` : `standing:${row.dataset.standingRow}`;
            const oldRect = before.positions.get(key);

            if (!oldRect) {
                if (row.dataset.standingRow) {
                    row.animate([
                        { opacity: 0, transform: 'translateY(-12px)' },
                        { opacity: 1, transform: 'translateY(0)' },
                    ], { duration: 400, easing: 'ease-out' });
                }
                return;
            }

            const newRect = row.getBoundingClientRect();
            const x = oldRect.left - newRect.left;
            const y = oldRect.top - newRect.top;

            if (Math.abs(x) < 1 && Math.abs(y) < 1) return;

            row.animate([
                { transform: `translate(${x}px, ${y}px)` },
                { transform: 'translate(0, 0)' },
            ], { duration: 500, easing: 'ease-out' });
        });
    });
}

if (window.Livewire) {
    registerAuctionAnimations();
} else {
    document.addEventListener('livewire:init', registerAuctionAnimations, { once: true });
}

if (window.Alpine) {
    registerCountdown();
} else {
    document.addEventListener('alpine:init', registerCountdown, { once: true });
}
