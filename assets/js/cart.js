/**
 * Cart Manager (AJAX)
 * Online Food Ordering System - Version 1.0
 */

const Cart = {
    baseUrl: window.APP_BASE_URL || '',

    init() {
        document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const restaurantId = btn.dataset.restaurantId;
                const restaurantName = btn.dataset.restaurantName;
                const itemId = btn.dataset.itemId;
                const itemName = btn.dataset.itemName;
                const itemPrice = parseFloat(btn.dataset.itemPrice);
                const isVeg = btn.dataset.isVeg || 1;

                this.addItem(restaurantId, restaurantName, itemId, itemName, itemPrice, 1, isVeg);
            });
        });

        // Cart quantity update buttons
        document.querySelectorAll('.cart-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const itemId = btn.dataset.itemId;
                const action = btn.dataset.action; // 'inc' or 'dec'
                const input = document.getElementById(`cartQty_${itemId}`);
                if (!input) return;

                let currentQty = parseInt(input.value) || 0;
                let newQty = action === 'inc' ? currentQty + 1 : currentQty - 1;
                this.updateItem(itemId, newQty);
            });
        });
    },

    async addItem(restaurantId, restaurantName, itemId, name, price, quantity = 1, isVeg = 1) {
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('restaurant_id', restaurantId);
            formData.append('restaurant_name', restaurantName);
            formData.append('item_id', itemId);
            formData.append('name', name);
            formData.append('price', price);
            formData.append('quantity', quantity);
            formData.append('is_veg', isVeg);

            const res = await fetch(`${this.baseUrl}/api/cart_action.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.conflict) {
                // Show conflict modal
                this.showConflictModal(restaurantId, restaurantName, itemId, name, price, isVeg, data.existing_restaurant);
                return;
            }

            if (data.success) {
                this.updateBadge(data.totals.items_count);
                showToast(`Added "${name}" to your cart!`, 'success');
            } else {
                showToast(data.message || 'Could not add item to cart', 'error');
            }
        } catch (err) {
            console.error('Cart add error:', err);
            showToast('Network error while updating cart', 'error');
        }
    },

    async updateItem(itemId, quantity) {
        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('item_id', itemId);
            formData.append('quantity', quantity);

            const res = await fetch(`${this.baseUrl}/api/cart_action.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                this.updateBadge(data.totals.items_count);
                // Reload current cart page if we're on cart.php or checkout.php
                if (window.location.pathname.includes('cart.php') || window.location.pathname.includes('checkout.php')) {
                    window.location.reload();
                } else {
                    showToast('Cart updated', 'info');
                }
            }
        } catch (err) {
            console.error('Cart update error:', err);
        }
    },

    async clearCartAndAdd(restaurantId, restaurantName, itemId, name, price, isVeg) {
        try {
            const formData = new FormData();
            formData.append('action', 'clear');
            await fetch(`${this.baseUrl}/api/cart_action.php`, { method: 'POST', body: formData });
            // Now add new item
            this.addItem(restaurantId, restaurantName, itemId, name, price, 1, isVeg);
            const modal = document.getElementById('cartConflictModal');
            if (modal) modal.classList.remove('show');
        } catch (err) {
            console.error(err);
        }
    },

    showConflictModal(restaurantId, restaurantName, itemId, name, price, isVeg, existingName) {
        let modal = document.getElementById('cartConflictModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'cartConflictModal';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-box">
                    <h3 style="margin-bottom:1rem;color:#0f172a;">Replace cart items?</h3>
                    <p style="color:#64748b;margin-bottom:1.5rem;font-size:0.95rem;">
                        Your cart contains items from <strong>${existingName}</strong>. Do you want to discard them and start a new order from <strong>${restaurantName}</strong>?
                    </p>
                    <div style="display:flex;gap:1rem;justify-content:flex-end;">
                        <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmReplaceCartBtn" class="btn btn-primary">Start New Cart</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.querySelector('[data-dismiss="modal"]').addEventListener('click', () => modal.classList.remove('show'));
        }

        const confirmBtn = document.getElementById('confirmReplaceCartBtn');
        confirmBtn.onclick = () => {
            this.clearCartAndAdd(restaurantId, restaurantName, itemId, name, price, isVeg);
        };

        modal.classList.add('show');
    },

    updateBadge(count) {
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(b => {
            b.textContent = count;
            b.style.display = count > 0 ? 'inline-block' : 'none';
        });
    }
};

document.addEventListener('DOMContentLoaded', () => Cart.init());
