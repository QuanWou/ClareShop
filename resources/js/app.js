import '@fontsource/be-vietnam-pro/latin-400.css';
import '@fontsource/be-vietnam-pro/latin-500.css';
import '@fontsource/be-vietnam-pro/latin-600.css';
import '@fontsource/be-vietnam-pro/latin-700.css';
import '@fontsource/be-vietnam-pro/vietnamese-400.css';
import '@fontsource/be-vietnam-pro/vietnamese-500.css';
import '@fontsource/be-vietnam-pro/vietnamese-600.css';
import '@fontsource/be-vietnam-pro/vietnamese-700.css';
import '@fontsource/noto-serif/latin-400.css';
import '@fontsource/noto-serif/latin-500.css';
import '@fontsource/noto-serif/latin-600.css';
import '@fontsource/noto-serif/latin-700.css';
import '@fontsource/noto-serif/vietnamese-400.css';
import '@fontsource/noto-serif/vietnamese-500.css';
import '@fontsource/noto-serif/vietnamese-600.css';
import '@fontsource/noto-serif/vietnamese-700.css';
import './auth.js';
import './storefront-motion.js';

if (document.querySelector('[data-rich-text-editor]')) {
    import('./admin-rich-text.js');
}

document.querySelectorAll('[data-storefront-toast]').forEach((toast) => {
    const dismiss = () => {
        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 220);
    };
    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 4500);
});

const productGallery = document.querySelector('[data-product-gallery]');
const productGalleryMain = productGallery?.querySelector('[data-gallery-main]');
const productGalleryThumbnails = productGallery?.querySelectorAll('[data-gallery-thumbnail]') ?? [];
const productGalleryLightbox = document.querySelector('[data-gallery-lightbox]');
const productGalleryLightboxImage = productGalleryLightbox?.querySelector('[data-gallery-lightbox-image]');

const selectProductGalleryImage = (url, alt) => {
    if (!productGalleryMain || !url) {
        return;
    }

    productGalleryMain.classList.add('is-changing');
    productGalleryMain.src = url;
    productGalleryMain.alt = alt || productGalleryMain.alt;

    if (productGalleryLightboxImage) {
        productGalleryLightboxImage.src = url;
        productGalleryLightboxImage.alt = alt || productGalleryMain.alt;
    }

    productGalleryThumbnails.forEach((thumbnail) => {
        const isCurrent = thumbnail.dataset.imageUrl === url;

        thumbnail.classList.toggle('is-current', isCurrent);
        thumbnail.setAttribute('aria-pressed', isCurrent ? 'true' : 'false');
    });

    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => productGalleryMain.classList.remove('is-changing'));
    });
};

productGalleryThumbnails.forEach((thumbnail) => {
    thumbnail.addEventListener('click', () => {
        selectProductGalleryImage(thumbnail.dataset.imageUrl, thumbnail.dataset.imageAlt);
    });
});

document.querySelector('[data-gallery-lightbox-open]')?.addEventListener('click', () => {
    if (productGalleryLightbox?.showModal) {
        productGalleryLightbox.showModal();
    }
});

productGalleryLightbox?.querySelector('[data-gallery-lightbox-close]')?.addEventListener('click', () => {
    productGalleryLightbox.close();
});

productGalleryLightbox?.addEventListener('click', (event) => {
    if (event.target === productGalleryLightbox) {
        productGalleryLightbox.close();
    }
});

const optionGroups = document.querySelectorAll('[data-product-options]');

optionGroups.forEach((group) => {
    const options = group.querySelectorAll('[data-variant-option]');
    const price = group.querySelector('[data-current-price]');
    const comparePrice = group.querySelector('[data-compare-price]');
    const selectedColor = group.querySelector('[data-selected-color]');
    const stockStatus = group.querySelector('[data-stock-status]');
    const addCartButton = group.querySelector('[data-add-cart-button]');
    const quantityInput = group.querySelector('[data-quantity-input]');
    const buyNowForm = group.querySelector('[data-buy-now-form]');
    const buyNowVariant = buyNowForm?.querySelector('[data-buy-now-variant]');
    const buyNowQuantity = buyNowForm?.querySelector('[data-buy-now-quantity]');
    const buyNowButton = buyNowForm?.querySelector('[data-buy-now-button]');

    options.forEach((option) => {
        option.addEventListener('change', () => {
            if (!option.checked) {
                return;
            }

            price.textContent = option.dataset.price;
            selectedColor.textContent = option.dataset.colorName;
            stockStatus.lastChild.textContent = ` ${option.dataset.stockLabel}`;
            stockStatus.classList.toggle('is-out-of-stock', option.dataset.inStock !== 'true');
            addCartButton.disabled = option.dataset.inStock !== 'true';
            addCartButton.textContent = option.dataset.inStock === 'true' ? 'Thêm vào giỏ' : 'Tạm hết hàng';
            if (buyNowVariant) buyNowVariant.value = option.value;
            if (buyNowButton) buyNowButton.disabled = option.dataset.inStock !== 'true';
            quantityInput.max = Math.max(1, Number(option.dataset.stockQuantity));

            selectProductGalleryImage(option.dataset.imageUrl, option.dataset.imageAlt);

            if (Number(quantityInput.value) > Number(quantityInput.max)) {
                quantityInput.value = quantityInput.max;
            }

            if (option.dataset.comparePrice) {
                comparePrice.textContent = option.dataset.comparePrice;
                comparePrice.classList.remove('is-hidden');
            } else {
                comparePrice.textContent = '';
                comparePrice.classList.add('is-hidden');
            }
        });
    });

    quantityInput?.addEventListener('input', () => {
        if (buyNowQuantity) buyNowQuantity.value = quantityInput.value;
    });
});

const cartPreview = document.querySelector('[data-cart-preview]');
const cartCount = document.querySelector('[data-cart-count]');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const motionIsReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.querySelectorAll('[data-saved-address]').forEach((addressOption) => {
    addressOption.addEventListener('change', () => {
        if (!addressOption.checked) {
            return;
        }

        const checkoutForm = addressOption.closest('[data-checkout-form]');
        const addressFields = {
            shipping_recipient_name: addressOption.dataset.recipientName,
            shipping_phone: addressOption.dataset.phone,
            shipping_address_line_1: addressOption.dataset.addressLine1,
            shipping_address_line_2: addressOption.dataset.addressLine2,
            shipping_ward: addressOption.dataset.ward,
            shipping_district: addressOption.dataset.district,
            shipping_city: addressOption.dataset.city,
            shipping_postal_code: addressOption.dataset.postalCode,
        };

        Object.entries(addressFields).forEach(([name, value]) => {
            const field = checkoutForm?.elements.namedItem(name);

            if (field) {
                field.value = value || '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });
});

document.querySelectorAll('[data-wishlist-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('[data-wishlist-button]');
        if (!button || button.disabled) return;

        button.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Không thể cập nhật yêu thích.');
            const active = payload.data.active;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            const icon = button.querySelector('span[aria-hidden="true"]');
            if (icon) icon.textContent = active ? '♥' : '♡';
            if (form.classList.contains('product-detail-wishlist')) {
                button.lastChild.textContent = active ? ' Đã lưu yêu thích' : ' Lưu vào yêu thích';
            }
        } catch (error) {
            window.alert(error.message);
        } finally {
            button.disabled = false;
        }
    });
});

document.querySelectorAll('[data-header-search-form]').forEach((searchForm) => {
    const input = searchForm.querySelector('[data-search-input]');
    const suggestions = searchForm.querySelector('[data-search-suggestions]');
    const suggestionsUrl = searchForm.dataset.searchSuggestionsUrl;
    let searchTimer;
    let searchController;

    if (!input || !suggestions || !suggestionsUrl) return;

    input.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchController?.abort();
        const term = input.value.trim();
        if (term.length < 2) {
            suggestions.hidden = true;
            suggestions.replaceChildren();
            return;
        }

        searchTimer = window.setTimeout(async () => {
            searchController = new AbortController();
            suggestions.hidden = false;
            suggestions.textContent = 'Đang tìm những mẫu phù hợp…';
            try {
                const url = new URL(suggestionsUrl, window.location.origin);
                url.searchParams.set('q', term);
                const response = await fetch(url, { signal: searchController.signal, headers: { Accept: 'application/json' } });
                const payload = await response.json();
                suggestions.replaceChildren();
                payload.data.forEach((product) => {
                    const link = document.createElement('a');
                    link.href = product.url;
                    if (product.image) {
                        const image = document.createElement('img');
                        image.src = product.image;
                        image.alt = '';
                        link.append(image);
                    }
                    const copy = document.createElement('span');
                    const name = document.createElement('strong');
                    const price = document.createElement('small');
                    name.textContent = product.name;
                    price.textContent = product.price;
                    copy.append(name, price);
                    link.append(copy);
                    suggestions.append(link);
                });
                if (!payload.data.length) suggestions.textContent = 'Chưa thấy mẫu phù hợp. Nhấn Enter để xem tìm kiếm đầy đủ.';
            } catch (error) {
                if (error.name !== 'AbortError') suggestions.hidden = true;
            }
        }, 180);
    });
});

document.querySelectorAll('[data-lamp-toggle]').forEach((control) => {
    const scene = control.closest('[data-lamp-scene]');
    const instruction = scene?.querySelector('[data-lamp-instruction]');
    const status = scene?.querySelector('[data-lamp-status]');

    if (!scene || !instruction || !status) {
        return;
    }

    let isLit = false;
    let activePointerId;
    let pullStartY = 0;
    let furthestPull = 0;
    let ignoreNextClick = false;

    const updateLamp = (nextState) => {
        isLit = nextState;
        scene.classList.toggle('is-lamp-on', isLit);
        control.setAttribute('aria-pressed', isLit ? 'true' : 'false');
        control.setAttribute('aria-label', isLit ? 'Kéo dây để tắt đèn' : 'Kéo dây để bật đèn');
        instruction.textContent = isLit ? 'Đèn đã bật · Kéo dây để tắt' : 'Kéo dây để bật đèn';
        status.textContent = isLit ? 'Đèn đã bật. Kéo dây lần nữa để tắt.' : 'Đèn đã tắt. Kéo dây để bật.';
    };

    const resetCord = () => {
        scene.classList.remove('is-pulling');
        control.style.removeProperty('--lamp-pull-distance');
    };

    const finishPull = (event, shouldToggle) => {
        if (event.pointerId !== activePointerId) {
            return;
        }

        const didPull = furthestPull >= 34;

        if (control.hasPointerCapture(event.pointerId)) {
            control.releasePointerCapture(event.pointerId);
        }

        activePointerId = undefined;
        resetCord();

        if (shouldToggle && didPull) {
            updateLamp(!isLit);
            ignoreNextClick = true;
        }
    };

    control.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        activePointerId = event.pointerId;
        pullStartY = event.clientY;
        furthestPull = 0;
        control.setPointerCapture(event.pointerId);
        scene.classList.add('is-pulling');
    });

    control.addEventListener('pointermove', (event) => {
        if (event.pointerId !== activePointerId) {
            return;
        }

        const pullDistance = Math.max(0, Math.min(event.clientY - pullStartY, 92));
        furthestPull = Math.max(furthestPull, pullDistance);
        control.style.setProperty('--lamp-pull-distance', `${pullDistance}px`);
    });

    control.addEventListener('pointerup', (event) => finishPull(event, true));
    control.addEventListener('pointercancel', (event) => finishPull(event, false));

    control.addEventListener('click', () => {
        if (ignoreNextClick) {
            ignoreNextClick = false;

            return;
        }

        updateLamp(!isLit);
    });

    updateLamp(false);
});

const updateCartSummary = (count) => {
    if (!cartPreview || !cartCount) {
        return;
    }

    cartCount.textContent = count;
    cartPreview.setAttribute('aria-label', `Giỏ hàng, hiện có ${count} sản phẩm`);
};

const receiveCartItem = () => {
    if (!cartPreview) {
        return;
    }

    cartPreview.classList.remove('is-cart-receiving');
    window.requestAnimationFrame(() => cartPreview.classList.add('is-cart-receiving'));
    window.setTimeout(() => cartPreview.classList.remove('is-cart-receiving'), 520);
};

const animateItemToCart = (form) => {
    if (motionIsReduced || !cartPreview) {
        receiveCartItem();

        return;
    }

    const productImage = document.querySelector(form.dataset.productImageSelector);
    const source = productImage ?? form.querySelector('[data-add-cart-button]');
    const sourceRect = source?.getBoundingClientRect();
    const targetRect = cartPreview.getBoundingClientRect();

    if (!sourceRect || !targetRect) {
        receiveCartItem();

        return;
    }

    const flyingItem = productImage?.cloneNode(true) ?? document.createElement('span');
    flyingItem.classList.add('cart-flying-item');
    flyingItem.setAttribute('aria-hidden', 'true');
    flyingItem.style.width = `${Math.min(sourceRect.width, 112)}px`;
    flyingItem.style.height = `${Math.min(sourceRect.height, 112)}px`;
    flyingItem.style.left = `${sourceRect.left + (sourceRect.width - Math.min(sourceRect.width, 112)) / 2}px`;
    flyingItem.style.top = `${sourceRect.top + (sourceRect.height - Math.min(sourceRect.height, 112)) / 2}px`;
    document.body.append(flyingItem);

    const deltaX = targetRect.left + targetRect.width / 2 - (sourceRect.left + sourceRect.width / 2);
    const deltaY = targetRect.top + targetRect.height / 2 - (sourceRect.top + sourceRect.height / 2);
    const animation = flyingItem.animate(
        [
            { opacity: 0.96, transform: 'translate3d(0, 0, 0) scale(1)', borderRadius: '0px' },
            { opacity: 0.82, transform: `translate3d(${deltaX * 0.55}px, ${deltaY * 0.2 - 42}px, 0) scale(0.72)`, borderRadius: '14px' },
            { opacity: 0.08, transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.16)`, borderRadius: '50%' },
        ],
        {
            duration: 650,
            easing: 'cubic-bezier(0.22, 0.76, 0.2, 1)',
            fill: 'forwards',
        },
    );

    animation.finished
        .catch(() => undefined)
        .finally(() => {
            flyingItem.remove();
            receiveCartItem();
        });
};

document.querySelectorAll('[data-add-cart-form]').forEach((form) => {
    const button = form.querySelector('[data-add-cart-button]');
    const feedback = form.querySelector('[data-cart-feedback]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!button || button.disabled) {
            return;
        }

        const originalLabel = button.textContent;
        button.disabled = true;
        button.textContent = 'Đang thêm…';
        form.setAttribute('aria-busy', 'true');
        feedback.textContent = '';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errors = Object.values(payload.errors ?? {}).flat();
                throw new Error(errors[0] ?? payload.message ?? 'Không thể thêm sản phẩm vào giỏ lúc này.');
            }

            updateCartSummary(payload.data.cart_item_count);
            animateItemToCart(form);
            feedback.textContent = payload.data.message;
            button.classList.add('is-added');
            button.textContent = 'Đã thêm vào giỏ';

            window.setTimeout(() => {
                const selectedVariant = form.querySelector('[data-variant-option]:checked');
                button.classList.remove('is-added');
                button.disabled = selectedVariant?.dataset.inStock !== 'true';
                button.textContent = button.disabled ? 'Tạm hết hàng' : originalLabel;
            }, 1800);
        } catch (error) {
            feedback.textContent = error.message;
            button.textContent = originalLabel;
            button.disabled = false;
        } finally {
            form.removeAttribute('aria-busy');
        }
    });
});

document.querySelectorAll('[data-mobile-menu]').forEach((menu) => {
    const summary = menu.querySelector('summary');

    menu.addEventListener('toggle', () => {
        summary.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menu.removeAttribute('open');
        });
    });

    menu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !menu.open) {
            return;
        }

        menu.removeAttribute('open');
        summary.focus();
    });
});

const navigationMenus = document.querySelectorAll('.desktop-nav .nav-menu');

navigationMenus.forEach((menu) => {
    const summary = menu.querySelector('summary');
    let closeTimer;

    const closeMenu = () => {
        window.clearTimeout(closeTimer);

        if (menu.matches(':focus-within')) {
            return;
        }

        menu.removeAttribute('open');
        summary.setAttribute('aria-expanded', 'false');
    };

    const openMenu = () => {
        window.clearTimeout(closeTimer);

        navigationMenus.forEach((otherMenu) => {
            if (otherMenu === menu) {
                return;
            }

            otherMenu.removeAttribute('open');
            otherMenu.querySelector('summary').setAttribute('aria-expanded', 'false');
        });

        menu.setAttribute('open', '');
        summary.setAttribute('aria-expanded', 'true');
    };

    menu.addEventListener('toggle', () => {
        if (menu.open) {
            navigationMenus.forEach((otherMenu) => {
                if (otherMenu !== menu) {
                    otherMenu.removeAttribute('open');
                }
            });
        }

        summary.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
    });

    menu.addEventListener('pointerenter', (event) => {
        if (event.pointerType === 'mouse') {
            openMenu();
        }
    });

    menu.addEventListener('pointerleave', (event) => {
        if (event.pointerType === 'mouse') {
            closeTimer = window.setTimeout(closeMenu, 120);
        }
    });

    menu.addEventListener('focusin', openMenu);
    menu.addEventListener('focusout', () => {
        closeTimer = window.setTimeout(closeMenu, 0);
    });

    menu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !menu.open) {
            return;
        }

        menu.removeAttribute('open');
        summary.setAttribute('aria-expanded', 'false');
        summary.focus();
    });

    menu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menu.removeAttribute('open');
            summary.setAttribute('aria-expanded', 'false');
        });
    });
});

const siteHeader = document.querySelector('.site-header');
const headerSearchForm = document.querySelector('[data-header-search-form]');
const searchOpenButton = document.querySelector('[data-search-open]');
const searchCloseButton = document.querySelector('[data-search-close]');
const searchInput = document.querySelector('[data-search-input]');

if (siteHeader && headerSearchForm && searchOpenButton && searchCloseButton && searchInput) {
    const closeHeaderSearch = ({ restoreFocus = true } = {}) => {
        if (!siteHeader.classList.contains('is-searching')) {
            return;
        }

        siteHeader.classList.remove('is-searching');
        headerSearchForm.setAttribute('aria-hidden', 'true');
        searchOpenButton.setAttribute('aria-expanded', 'false');
        searchOpenButton.setAttribute('aria-label', 'Mở tìm kiếm sản phẩm');

        if (restoreFocus) {
            searchOpenButton.focus();
        }
    };

    const openHeaderSearch = () => {
        siteHeader.classList.add('is-searching');
        headerSearchForm.setAttribute('aria-hidden', 'false');
        searchOpenButton.setAttribute('aria-expanded', 'true');
        searchOpenButton.setAttribute('aria-label', 'Đóng tìm kiếm sản phẩm');

        window.requestAnimationFrame(() => searchInput.focus({ preventScroll: true }));
    };

    searchOpenButton.addEventListener('click', () => {
        if (siteHeader.classList.contains('is-searching')) {
            closeHeaderSearch();

            return;
        }

        openHeaderSearch();
    });

    searchCloseButton.addEventListener('click', () => closeHeaderSearch());

    document.addEventListener('pointerdown', (event) => {
        if (!siteHeader.classList.contains('is-searching') || siteHeader.contains(event.target)) {
            return;
        }

        closeHeaderSearch({ restoreFocus: false });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeHeaderSearch();
        }
    });
}

const checkoutForm = document.querySelector('[data-checkout-form]');

if (checkoutForm) {
    const quoteButton = checkoutForm.querySelector('[data-checkout-quote]');
    const shippingFields = checkoutForm.querySelectorAll('[data-shipping-field]');
    const shippingTotal = checkoutForm.querySelector('[data-checkout-shipping]');
    const deliveryEstimate = checkoutForm.querySelector('[data-checkout-eta]');
    const discountTotal = checkoutForm.querySelector('[data-checkout-discount]');
    const discountFeedback = checkoutForm.querySelector('[data-checkout-discount-feedback]');
    const orderTotal = checkoutForm.querySelector('[data-checkout-total]');
    const quoteStatus = checkoutForm.querySelector('[data-checkout-quote-status]');
    const discountCode = checkoutForm.querySelector('[data-checkout-discount-code]');
    const shippingDetails = checkoutForm.querySelector('[data-checkout-shipping-details]');
    const shippingProvider = checkoutForm.querySelector('[data-checkout-shipping-provider]');
    const shippingService = checkoutForm.querySelector('[data-checkout-shipping-service]');
    const shippingWeight = checkoutForm.querySelector('[data-checkout-shipping-weight]');
    const shippingRule = checkoutForm.querySelector('[data-checkout-shipping-rule]');
    const shippingOptionInputs = checkoutForm.querySelectorAll('[data-shipping-option]');
    const shippingOptionPrices = checkoutForm.querySelectorAll('[data-shipping-option-price]');
    const shippingOptionEtas = checkoutForm.querySelectorAll('[data-shipping-option-eta]');
    const initialTotal = orderTotal?.textContent ?? '';
    let quoteTimer;
    let activeQuoteRequest;

    const hasValidShippingAddress = (reportValidity = false) => [...shippingFields].every((field) => (
        reportValidity ? field.reportValidity() : field.checkValidity()
    ));

    const setDiscountFeedback = (message = '', state = '') => {
        if (!discountFeedback) {
            return;
        }

        discountFeedback.textContent = message;
        discountFeedback.hidden = message === '';
        discountFeedback.dataset.state = state;
    };

    const resetQuote = (message) => {
        shippingTotal.textContent = 'Nhập địa chỉ để ước tính';
        deliveryEstimate.textContent = 'Hoàn thiện địa chỉ để xem';
        discountTotal.textContent = '—';
        orderTotal.textContent = initialTotal;
        shippingDetails.hidden = true;
        shippingOptionPrices.forEach((price) => {
            price.textContent = 'Nhập địa chỉ';
        });
        shippingOptionEtas.forEach((eta) => {
            eta.textContent = 'để xem phí';
        });
        quoteStatus.textContent = message;
    };

    const shippingRuleCopy = (shipping) => {
        const calculation = shipping.calculation ?? {};
        const parts = [];

        if (calculation.base_fee_formatted && calculation.included_weight_grams) {
            parts.push(`${calculation.base_fee_formatted} cho ${calculation.included_weight_grams}g đầu`);
        }

        if (calculation.additional_weight_blocks > 0 && calculation.additional_weight_fee_formatted && calculation.additional_weight_block_grams) {
            parts.push(`+ ${calculation.additional_weight_blocks} × ${calculation.additional_weight_fee_formatted} mỗi ${calculation.additional_weight_block_grams}g`);
        }

        if (calculation.destination_surcharge > 0 && calculation.destination_surcharge_formatted) {
            parts.push(`+ ${calculation.destination_surcharge_formatted} khu vực ngoại thành/tỉnh`);
        } else if (calculation.is_urban_destination === true) {
            parts.push('không phụ thu khu vực nội thành');
        }

        return parts.join(' · ') || 'Được tính theo địa chỉ và khối lượng đơn.';
    };

    const applyQuote = (data) => {
        const { shipping, discount } = data;

        shippingTotal.textContent = shipping.fee_formatted;
        deliveryEstimate.textContent = shipping.estimated_delivery_date_formatted ?? shipping.estimated_days_label ?? 'Đang cập nhật';
        orderTotal.textContent = data.total_formatted;
        shippingProvider.textContent = shipping.provider;
        shippingService.textContent = shipping.service;
        shippingWeight.textContent = `${shipping.total_weight_grams.toLocaleString('vi-VN')} g`;
        shippingRule.textContent = shippingRuleCopy(shipping);
        shippingDetails.hidden = false;

        (data.shipping_options ?? []).forEach((option) => {
            const price = checkoutForm.querySelector(`[data-shipping-option-price="${option.option}"]`);
            const eta = checkoutForm.querySelector(`[data-shipping-option-eta="${option.option}"]`);

            if (price) {
                price.textContent = option.fee_formatted;
            }

            if (eta) {
                eta.textContent = option.estimated_delivery_date_formatted ?? option.estimated_days_label ?? 'Đang cập nhật';
            }
        });

        if (discount.applied) {
            discountTotal.textContent = `-${discount.amount_formatted}`;
            setDiscountFeedback(
                `Đã áp dụng ${discount.code}${discount.name ? ` — ${discount.name}` : ''}: giảm ${discount.amount_formatted}.`,
                'success',
            );
        } else {
            discountTotal.textContent = '—';
            setDiscountFeedback(discount.message ?? (discountCode?.value.trim() ? 'Mã chưa tạo ưu đãi cho đơn này.' : ''), discount.message ? 'error' : '');
        }

        const etaCopy = shipping.estimated_delivery_date_formatted
            ? `Nhận dự kiến ${shipping.estimated_delivery_date_formatted}.`
            : 'Ngày nhận dự kiến đang được cập nhật.';
        quoteStatus.textContent = `${shipping.provider} đã được chọn. ${etaCopy} Phí ship là ước tính nội bộ, chưa phải báo giá chính thức của đơn vị vận chuyển.`;
    };

    const quoteShipping = async ({ reportValidity = false } = {}) => {
        if (!hasValidShippingAddress(reportValidity)) {
            quoteStatus.textContent = 'Vui lòng hoàn thiện thông tin giao hàng bắt buộc trước khi tính phí ship và kiểm tra ưu đãi.';
            return;
        }

        if (activeQuoteRequest) {
            activeQuoteRequest.abort();
        }

        const quoteRequest = new AbortController();
        activeQuoteRequest = quoteRequest;
        const values = new FormData(checkoutForm);
        const address = Object.fromEntries(
            [...shippingFields].map((field) => [field.name, values.get(field.name) ?? '']),
        );
        address.discount_code = discountCode?.value ?? '';
        address.shipping_option = checkoutForm.querySelector('[data-shipping-option]:checked')?.value ?? '';

        quoteButton.disabled = true;
        quoteStatus.textContent = 'Đang tính phí ship, ngày nhận dự kiến và kiểm tra ưu đãi…';
        setDiscountFeedback(discountCode?.value.trim() ? 'Đang kiểm tra mã ưu đãi…' : '');

        try {
            const response = await fetch(checkoutForm.dataset.quoteUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(address),
                signal: quoteRequest.signal,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessages = Object.values(payload.errors ?? {}).flat();
                const promotionMessage = payload.errors?.discount_code?.[0];

                if (promotionMessage) {
                    discountTotal.textContent = '—';
                    setDiscountFeedback(promotionMessage, 'error');
                }

                throw new Error(validationMessages[0] ?? payload.message ?? 'Không thể tính phí ship lúc này.');
            }

            applyQuote(payload.data);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            quoteStatus.textContent = error.message;
        } finally {
            if (activeQuoteRequest === quoteRequest) {
                quoteButton.disabled = false;
                activeQuoteRequest = undefined;
            }
        }
    };

    const scheduleQuote = () => {
        window.clearTimeout(quoteTimer);

        if (!hasValidShippingAddress()) {
            return;
        }

        quoteTimer = window.setTimeout(() => quoteShipping(), 500);
    };

    quoteButton.addEventListener('click', () => quoteShipping({ reportValidity: true }));

    shippingFields.forEach((field) => {
        field.addEventListener('input', () => {
            resetQuote('Thông tin giao hàng đã thay đổi. Hệ thống sẽ tính lại phí ship và ngày nhận dự kiến.');
            scheduleQuote();
        });
    });

    shippingOptionInputs.forEach((input) => {
        input.addEventListener('change', () => {
            resetQuote('Đơn vị vận chuyển đã thay đổi. Hệ thống sẽ tính lại phí ship và ngày nhận dự kiến.');
            scheduleQuote();
        });
    });

    discountCode?.addEventListener('input', () => {
        discountCode.value = discountCode.value.toUpperCase();
        setDiscountFeedback(discountCode.value.trim() ? 'Đang chờ kiểm tra mã ưu đãi…' : '');
        scheduleQuote();
    });

    checkoutForm.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.target.tagName !== 'INPUT' || event.target.type === 'radio') {
            return;
        }

        event.preventDefault();

        if (event.target === discountCode || [...shippingFields].includes(event.target)) {
            quoteShipping({ reportValidity: false });
        }
    });

    window.setTimeout(scheduleQuote, 50);
}

const appointmentForm = document.querySelector('[data-appointment-form]');

if (appointmentForm) {
    const appointmentTypes = appointmentForm.querySelectorAll('input[name="type"]');
    const addressFields = appointmentForm.querySelectorAll('[data-appointment-address-field]');
    const addressCopy = appointmentForm.querySelector('[data-appointment-address-copy]');

    const syncAddressRequirements = () => {
        const isInstallation = appointmentForm.querySelector('input[name="type"]:checked')?.value === 'installation';

        addressFields.forEach((field) => {
            field.required = isInstallation;
        });

        addressCopy.textContent = isInstallation
            ? 'Địa chỉ là thông tin bắt buộc để Clare ghi nhận yêu cầu lắp đặt.'
            : 'Không bắt buộc nếu bạn muốn tư vấn online. Các trường này cần có khi gửi yêu cầu lắp đặt.';
    };

    appointmentTypes.forEach((input) => {
        input.addEventListener('change', syncAddressRequirements);
    });

    syncAddressRequirements();
}

const catalogFilterPanel = document.querySelector('[data-catalog-filter-panel]');
const catalogFilterOpen = document.querySelector('[data-catalog-filter-open]');
const catalogFilterCloseButtons = document.querySelectorAll('[data-catalog-filter-close]');

if (catalogFilterPanel && catalogFilterOpen) {
    const closeCatalogFilters = () => {
        document.body.classList.remove('has-catalog-filters-open');
        catalogFilterOpen.setAttribute('aria-expanded', 'false');
        catalogFilterOpen.focus({ preventScroll: true });
    };

    catalogFilterOpen.addEventListener('click', () => {
        document.body.classList.add('has-catalog-filters-open');
        catalogFilterOpen.setAttribute('aria-expanded', 'true');
        catalogFilterPanel.querySelector('input, select, button')?.focus({ preventScroll: true });
    });

    catalogFilterCloseButtons.forEach((button) => button.addEventListener('click', closeCatalogFilters));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('has-catalog-filters-open')) {
            closeCatalogFilters();
        }
    });
}

document.querySelectorAll('[data-catalog-auto-submit]').forEach((control) => {
    control.addEventListener('change', () => {
        const form = document.getElementById(control.getAttribute('form'));

        if (!form) {
            return;
        }

        form.setAttribute('aria-busy', 'true');
        form.requestSubmit();
    });
});

document.querySelectorAll('#catalog-filter-form').forEach((form) => {
    form.addEventListener('submit', () => {
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.textContent = 'Đang lọc…';
        });
    });
});
