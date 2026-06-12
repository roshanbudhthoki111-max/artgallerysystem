// ArtVault — Main JavaScript

document.addEventListener('DOMContentLoaded', () => {

    // ── Toast notifications ────────────────────────────────────
    window.showToast = function(message, type = 'success') {
        const container = document.getElementById('toast-container') || (() => {
            const el = document.createElement('div');
            el.id = 'toast-container';
            el.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(el);
            return el;
        })();

        const id = 'toast-' + Date.now();
        const icons = { success: 'check-circle-fill', danger: 'x-circle-fill', info: 'info-circle-fill' };
        const colors = { success: '#27ae60', danger: '#c0392b', info: '#3b82f6' };
        const color = colors[type] || colors.info;
        const icon = icons[type] || icons.info;

        const html = `
        <div id="${id}" class="toast align-items-center border-0 shadow-sm" role="alert" style="background:white;border-left:3px solid ${color}!important;border-radius:8px;">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" style="font-size:.875rem;">
                    <i class="bi bi-${icon}" style="color:${color}"></i> ${message}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;

        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    };

    // ── AJAX action handler ─────────────────────────────────────
    document.querySelectorAll('[data-action]').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const action = this.dataset.action;
            const id = this.dataset.id;
            const confirm_msg = this.dataset.confirm;

            if (confirm_msg && !confirm(confirm_msg)) return;

            try {
                const res = await fetch('/art-gallery/api/action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action, id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message || 'Done!', 'success');
                    if (data.reload) setTimeout(() => location.reload(), 800);
                    if (data.redirect) setTimeout(() => location.href = data.redirect, 800);
                    // Toggle UI states
                    if (action === 'toggle_like') {
                        const countEl = this.querySelector('.like-count');
                        if (countEl) countEl.textContent = data.count;
                        this.classList.toggle('liked');
                        this.querySelector('i').classList.toggle('bi-heart');
                        this.querySelector('i').classList.toggle('bi-heart-fill');
                    }
                    if (action === 'toggle_wishlist') {
                        this.classList.toggle('wishlisted');
                        this.querySelector('i').classList.toggle('bi-bookmark');
                        this.querySelector('i').classList.toggle('bi-bookmark-fill');
                    }
                } else {
                    showToast(data.message || 'Something went wrong', 'danger');
                }
            } catch (err) {
                showToast('Network error', 'danger');
            }
        });
    });

    // ── Drag & drop upload ──────────────────────────────────────
    const uploadZone = document.querySelector('.upload-zone');
    if (uploadZone) {
        const fileInput = uploadZone.querySelector('input[type="file"]');
        uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
        uploadZone.addEventListener('drop', e => {
            e.preventDefault();
            uploadZone.classList.remove('drag-over');
            if (fileInput && e.dataTransfer.files[0]) {
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                fileInput.files = dt.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
        uploadZone.addEventListener('click', () => fileInput && fileInput.click());

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                const preview = uploadZone.querySelector('.upload-preview');
                const reader = new FileReader();
                reader.onload = e => {
                    if (preview) {
                        if (file.type.startsWith('image/')) {
                            preview.innerHTML = `<img src="${e.target.result}" style="max-height:200px;border-radius:8px;margin-top:1rem;">`;
                        } else {
                            preview.innerHTML = `<p class="mt-2 text-muted small"><i class="bi bi-film me-1"></i>${file.name}</p>`;
                        }
                    }
                    uploadZone.querySelector('.upload-placeholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            });
        }
    }

    // ── Offer price counter ─────────────────────────────────────
    const offerInput = document.getElementById('offer_price');
    const originalPrice = parseFloat(document.getElementById('artwork-price')?.dataset.price);
    if (offerInput && originalPrice) {
        offerInput.addEventListener('input', function() {
            const val = parseFloat(this.value);
            const pct = document.getElementById('offer-pct');
            if (pct && val) {
                const diff = ((val - originalPrice) / originalPrice * 100).toFixed(0);
                pct.textContent = diff >= 0 ? `+${diff}%` : `${diff}%`;
                pct.style.color = diff >= 0 ? '#27ae60' : '#c0392b';
            }
        });
    }

    // ── Image gallery / lightbox on artwork detail ──────────────
    document.querySelectorAll('.artwork-zoom').forEach(img => {
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.9);display:flex;align-items:center;justify-content:center;z-index:9999;cursor:zoom-out;';
            modal.innerHTML = `<img src="${this.src}" style="max-width:90%;max-height:90vh;border-radius:8px;box-shadow:0 24px 64px rgba(0,0,0,.4);">`;
            modal.addEventListener('click', () => modal.remove());
            document.body.appendChild(modal);
        });
    });

    // ── Price range display ─────────────────────────────────────
    const priceRange = document.getElementById('price_max');
    const priceDisplay = document.getElementById('price-display');
    if (priceRange && priceDisplay) {
        priceRange.addEventListener('input', function() {
            priceDisplay.textContent = 'NPR ' + Number(this.value).toLocaleString();
        });
    }

    // ── Smooth scroll to section ────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });

    // ── Auto-dismiss flash alerts ───────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 4000);
    });

    // ── Navbar scroll effect ────────────────────────────────────
    const nav = document.querySelector('.site-nav');
    if (nav) {
        window.addEventListener('scroll', () => {
            nav.style.boxShadow = window.scrollY > 10 ? '0 2px 20px rgba(0,0,0,.08)' : '';
        });
    }
});
