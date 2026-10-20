/**
 * Main Frontend Scripts
 * Online Food Ordering System - Version 1.0
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. User dropdown toggle
    const userPill = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    if (userPill && userDropdown) {
        userPill.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
        });
    }

    // 2. Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.querySelector('.nav-links');
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            if (navLinks.style.display === 'flex') {
                navLinks.style.display = 'none';
            } else {
                navLinks.style.display = 'flex';
                navLinks.style.flexDirection = 'column';
                navLinks.style.position = 'absolute';
                navLinks.style.top = '72px';
                navLinks.style.left = '0';
                navLinks.style.right = '0';
                navLinks.style.background = '#ffffff';
                navLinks.style.padding = '1.5rem';
                navLinks.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            }
        });
    }

    // 3. Modal close handlers
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) modal.classList.remove('show');
        });
    });

    // 4. Interactive Star Rating Selector
    const starPicker = document.querySelector('.interactive-star-rating');
    if (starPicker) {
        const stars = starPicker.querySelectorAll('.rating-star-btn');
        const input = document.getElementById('ratingValueInput');
        stars.forEach(star => {
            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.dataset.value);
                stars.forEach(s => {
                    s.classList.toggle('highlighted', parseInt(s.dataset.value) <= val);
                });
            });
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value);
                if (input) input.value = val;
                stars.forEach(s => {
                    s.classList.toggle('selected', parseInt(s.dataset.value) <= val);
                });
            });
        });
        starPicker.addEventListener('mouseleave', () => {
            const currentVal = input ? parseInt(input.value || 0) : 0;
            stars.forEach(s => {
                s.classList.remove('highlighted');
                s.classList.toggle('selected', parseInt(s.dataset.value) <= currentVal);
            });
        });
    }

    // 5. Auto dismiss flash alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        });
    }, 5000);
});

// Toast Notification Helper
function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#ff5722');
    toast.style.cssText = `background:${bg};color:white;padding:12px 20px;border-radius:10px;box-shadow:0 8px 16px rgba(0,0,0,0.15);font-size:0.9rem;font-weight:600;display:flex;align-items:center;gap:8px;transform:translateY(20px);opacity:0;transition:all 0.3s cubic-bezier(0.16,1,0.3,1);pointer-events:auto;`;
    toast.innerHTML = `<span>${message}</span>`;
    container.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.transform = 'translateY(20px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}
