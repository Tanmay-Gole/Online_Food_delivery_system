/**
 * Order Tracking Module (Real-Time Poller)
 * Enforces REQ-4.5.1 & REQ-4.5.4
 */

const Tracker = {
    orderId: null,
    pollInterval: 4000,
    timer: null,
    statusSequence: ['placed', 'accepted', 'preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered'],

    init(orderId) {
        this.orderId = orderId;
        this.startPolling();
    },

    startPolling() {
        if (!this.orderId) return;
        this.timer = setInterval(() => this.checkStatus(), this.pollInterval);
    },

    stopPolling() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    },

    async checkStatus() {
        try {
            const res = await fetch(`${window.APP_BASE_URL}/api/order_status.php?order_id=${this.orderId}`);
            if (!res.ok) return;
            const data = await res.json();
            if (data.success && data.order) {
                this.updateUI(data.order, data.history);
            }
        } catch (err) {
            console.error('Tracker polling error:', err);
        }
    },

    updateUI(order, history) {
        const status = order.order_status;
        const currentBadge = document.getElementById('orderStatusBadge');
        if (currentBadge) {
            currentBadge.textContent = status.replace('_', ' ').toUpperCase();
        }

        // Stepper updates
        const steps = ['placed', 'accepted', 'preparing', 'out_for_delivery', 'delivered'];
        let activeIdx = steps.indexOf(status === 'ready_for_pickup' ? 'preparing' : status);
        if (activeIdx === -1 && status === 'delivered') activeIdx = 4;

        steps.forEach((step, idx) => {
            const stepEl = document.getElementById(`step_${step}`);
            if (!stepEl) return;

            stepEl.classList.remove('completed', 'active');
            if (idx < activeIdx) {
                stepEl.classList.add('completed');
            } else if (idx === activeIdx) {
                stepEl.classList.add('active');
            }
        });

        const progressBar = document.getElementById('stepperProgressBar');
        if (progressBar && activeIdx >= 0) {
            const pct = Math.min(100, Math.round((activeIdx / (steps.length - 1)) * 100));
            progressBar.style.width = `${pct}%`;
        }

        // Check cancellation rule (Business Rule: Cannot cancel once 'Preparing')
        const cancelBtn = document.getElementById('cancelOrderBtn');
        if (cancelBtn) {
            if (['preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'cancelled'].includes(status)) {
                cancelBtn.style.display = 'none';
            }
        }

        // If delivered, show review banner if not rated yet
        if (status === 'delivered') {
            this.stopPolling();
            const reviewPrompt = document.getElementById('deliveredReviewPrompt');
            if (reviewPrompt) reviewPrompt.style.display = 'block';
        }

        if (status === 'cancelled') {
            this.stopPolling();
        }
    }
};
