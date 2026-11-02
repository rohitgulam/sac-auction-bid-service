const previousState = new Map();

function registerAuctionAnimations() {
    window.Livewire.hook('morph', ({ el, component }) => {
        const values = new Map();
        const cards = new Map();

        el.querySelectorAll('[data-live-key]').forEach((value) => {
            values.set(value.dataset.liveKey, value.dataset.liveValue);
        });
        el.querySelectorAll('[data-auction-card]').forEach((card) => {
            cards.set(card.dataset.auctionCard, card.getBoundingClientRect());
        });

        previousState.set(component.id, { values, cards });
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

        el.querySelectorAll('[data-auction-card]').forEach((card) => {
            const oldRect = before.cards.get(card.dataset.auctionCard);

            if (!oldRect) return;

            const newRect = card.getBoundingClientRect();
            const x = oldRect.left - newRect.left;
            const y = oldRect.top - newRect.top;

            if (Math.abs(x) < 1 && Math.abs(y) < 1) return;

            card.animate([
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
