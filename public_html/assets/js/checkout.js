/**
 * checkout.js — Checkout page logic for Dabhi Chikki
 * Real-time shipping, COD handling, and GST tax calculation matching admin configuration.
 */

(function () {
  'use strict';

  const summaryCard   = document.getElementById('order-summary');
  const businessState = (summaryCard?.dataset.businessState || 'Gujarat').toLowerCase().trim();
  const gstEnabled    = summaryCard?.dataset.gstEnabled === '1';

  const pincodeInput  = document.getElementById('pincode');
  const pincodeMsg    = document.getElementById('pincode-msg');
  const stateSelect   = document.getElementById('ship_state');
  let pincodeTimer;

  let currentShippingRate = null;
  let currentCodExtra     = 0;
  let isPincodeServiceable= true;

  function isCodSelected() {
    const codRadio = document.querySelector('input[name="payment_method"][value="cod"]');
    return codRadio ? codRadio.checked : false;
  }

  function getSubtotal() {
    const el = document.getElementById('summary-subtotal');
    return parseFloat(el?.dataset.value || 0);
  }

  function getDiscount() {
    const el = document.getElementById('coupon-discount');
    if (!el || !el.textContent) return 0;
    return parseFloat(el.textContent.replace(/[^0-9.]/g, '') || 0);
  }

  /* ── Pincode check ───────────────────────────────── */
  if (pincodeInput) {
    pincodeInput.addEventListener('input', () => {
      clearTimeout(pincodeTimer);
      const val = pincodeInput.value.replace(/\D/g, '');
      pincodeInput.value = val;
      if (val.length === 6) {
        pincodeTimer = setTimeout(() => checkPincode(val), 400);
      } else {
        setPincodeMsg('', '');
      }
    });

    // Initial check if pre-filled
    if (pincodeInput.value.replace(/\D/g, '').length === 6) {
      checkPincode(pincodeInput.value.replace(/\D/g, ''));
    }
  }

  async function checkPincode(pin) {
    setPincodeMsg('Checking delivery to ' + pin + '…', 'muted');
    try {
      const res = await fetch('api/pincode-check.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({
          pincode: pin,
          is_cod: isCodSelected(),
          subtotal: getSubtotal()
        })
      });
      const data = await res.json();
      if (data.ok) {
        isPincodeServiceable = true;
        currentShippingRate  = parseFloat(data.charge) || 0;
        currentCodExtra      = parseFloat(data.cod_extra) || 0;

        setPincodeMsg('✓ ' + data.message, 'success');

        // Check COD availability
        const codOption = document.getElementById('pm-cod');
        const codRadio  = codOption?.querySelector('input[type="radio"]');
        if (codOption) {
          if (!data.cod_available) {
            codOption.style.opacity = '0.5';
            if (codRadio?.checked) {
              const onlineOption = document.getElementById('pm-online');
              const onlineRadio  = onlineOption?.querySelector('input[type="radio"]');
              if (onlineOption && onlineRadio) {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                onlineOption.classList.add('selected');
                onlineRadio.checked = true;
                const note = document.getElementById('pm-online-note');
                if (note) note.style.display = 'flex';
                if (typeof showToast === 'function') {
                  showToast('Cash on Delivery is unavailable for PIN code ' + pin + '. Switched to online payment.', 'warning');
                }
              }
            }
          } else {
            codOption.style.opacity = '1';
          }
        }

        recalcTotal();
      } else {
        isPincodeServiceable = false;
        setPincodeMsg(data.error || 'Delivery not available to this PIN code.', 'error');
      }
    } catch (_) {
      setPincodeMsg('Could not verify PIN code. Continuing with standard delivery.', 'warning');
    }
  }

  function setPincodeMsg(msg, type) {
    if (!pincodeMsg) return;
    pincodeMsg.textContent = msg;
    pincodeMsg.className = 'form-hint';
    if (type === 'success') pincodeMsg.style.color = 'var(--success)';
    else if (type === 'error') pincodeMsg.style.color = 'var(--error)';
    else if (type === 'warning') pincodeMsg.style.color = 'var(--warning)';
    else pincodeMsg.style.color = 'var(--text-muted)';
  }

  /* ── State Change GST recalculation ──────────────── */
  if (stateSelect) {
    stateSelect.addEventListener('change', () => {
      recalcTotal();
    });
  }

  /* ── Payment Method Toggle ───────────────────────── */
  const payOptions = document.querySelectorAll('.payment-option');
  payOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      const radio = opt.querySelector('input[type=radio]');
      if (opt.style.opacity === '0.5') {
        if (typeof showToast === 'function') {
          showToast('Cash on Delivery is not available for the entered PIN code.', 'error');
        }
        return;
      }
      payOptions.forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      if (radio) radio.checked = true;

      const note = document.getElementById('pm-online-note');
      if (note) note.style.display = opt.id === 'pm-online' ? 'flex' : 'none';

      // Recheck pincode with updated COD flag if pincode is entered
      const pin = pincodeInput?.value.replace(/\D/g, '');
      if (pin && pin.length === 6) {
        checkPincode(pin);
      } else {
        recalcTotal();
      }
    });
  });

  /* ── Coupon Code ─────────────────────────────────── */
  const couponForm = document.getElementById('coupon-form');
  const couponMsg  = document.getElementById('coupon-msg');

  if (couponForm) {
    couponForm.addEventListener('submit', async e => {
      e.preventDefault();
      const code     = document.getElementById('coupon-code').value.trim().toUpperCase();
      const subtotal = getSubtotal();
      if (!code) return;

      try {
        const res  = await fetch('api/checkout.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ action: 'validate_coupon', code, subtotal })
        });
        const data = await res.json();
        if (data.ok) {
          couponMsg.textContent = '✓ ' + data.message;
          couponMsg.style.color = 'var(--success)';
          document.getElementById('coupon-discount').textContent = '−₹' + parseFloat(data.discount).toFixed(2);
          document.getElementById('coupon-discount-row').style.display = 'flex';
          document.getElementById('applied-coupon').value = code;
          recalcTotal();
        } else {
          couponMsg.textContent = data.error || 'Invalid coupon code.';
          couponMsg.style.color = 'var(--error)';
        }
      } catch (_) {
        couponMsg.textContent = 'Could not validate coupon.';
        couponMsg.style.color = 'var(--error)';
      }
    });
  }

  /* ── Grand Total & GST Recalculation ─────────────── */
  function recalcTotal() {
    const subtotalEl  = document.getElementById('summary-subtotal');
    const shippingEl  = document.getElementById('shipping-charge');
    const codFeeRow   = document.getElementById('cod-fee-row');
    const codFeeEl    = document.getElementById('cod-fee');
    const totalEl     = document.getElementById('summary-total');
    const gstRow      = document.getElementById('gst-breakdown-row');
    const gstLabel    = document.getElementById('gst-breakdown-label');
    const gstAmountEl = document.getElementById('gst-breakdown-amount');

    if (!subtotalEl || !totalEl) return;

    const sub        = getSubtotal();
    const disc       = getDiscount();
    const isCod      = isCodSelected();
    const taxableAmt = Math.max(0, sub - disc);

    // Free shipping threshold from summary card dataset
    const freeThreshold = parseFloat(summaryCard?.dataset.freeThreshold || 999);
    const defaultFee    = parseFloat(summaryCard?.dataset.defaultShipping || 60);

    let shippingFee = 0;
    if (currentShippingRate !== null) {
      shippingFee = currentShippingRate;
    } else {
      shippingFee = (freeThreshold > 0 && taxableAmt >= freeThreshold) ? 0 : defaultFee;
    }

    if (shippingEl) {
      shippingEl.innerHTML = shippingFee === 0
        ? '<span style="color:var(--success)">FREE</span>'
        : '₹' + shippingFee.toFixed(2);
    }

    // COD Handling fee
    let codFee = 0;
    if (isCod) {
      codFee = currentCodExtra > 0 ? currentCodExtra : 0;
    }
    if (codFeeRow && codFeeEl) {
      if (codFee > 0) {
        codFeeRow.style.display = 'flex';
        codFeeEl.textContent = '₹' + codFee.toFixed(2);
      } else {
        codFeeRow.style.display = 'none';
      }
    }

    // GST Calculation & Split Display
    if (gstEnabled && gstRow && gstLabel && gstAmountEl) {
      const selectedState = (stateSelect?.value || 'Gujarat').toLowerCase().trim();
      const isIntraState  = (selectedState === businessState);
      const estGstRate    = 0.05; // standard 5% on chikki confectionery
      const gstVal        = Math.round(taxableAmt * estGstRate * 100) / 100;

      gstRow.style.display = 'flex';
      if (isIntraState) {
        const half = (gstVal / 2).toFixed(2);
        gstLabel.textContent = 'CGST (2.5%) + SGST (2.5%)';
        gstAmountEl.textContent = '₹' + gstVal.toFixed(2) + ' (₹' + half + ' + ₹' + half + ')';
      } else {
        gstLabel.textContent = 'IGST (5%)';
        gstAmountEl.textContent = '₹' + gstVal.toFixed(2);
      }
    } else if (gstRow) {
      gstRow.style.display = 'none';
    }

    const finalGrandTotal = Math.max(0, taxableAmt + shippingFee + codFee);
    totalEl.textContent = '₹' + finalGrandTotal.toFixed(2);
  }

  /* ── Checkout Form Submit ─────────────────────────── */
  const checkoutForm = document.getElementById('checkout-form');
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = checkoutForm.querySelector('[type=submit]');
      setLoading(btn, true);

      const fd = new FormData(checkoutForm);
      const payload = Object.fromEntries(fd.entries());
      payload.action = 'place_order';

      try {
        const res  = await fetch('api/order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.ok) {
          window.location.href = 'order-success.php?id=' + data.order_id;
        } else if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          if (typeof showToast === 'function') {
            showToast(data.error || 'Order could not be placed. Please try again.', 'error');
          } else {
            alert(data.error || 'Order could not be placed. Please try again.');
          }
          setLoading(btn, false);
        }
      } catch (_) {
        if (typeof showToast === 'function') {
          showToast('Network error. Please try again.', 'error');
        } else {
          alert('Network error. Please try again.');
        }
        setLoading(btn, false);
      }
    });
  }

  function setLoading(btn, state) {
    if (!btn) return;
    btn.disabled = state;
    btn.classList.toggle('loading', state);
    const text = btn.querySelector('.btn-text');
    if (text) text.textContent = state ? 'Placing Order…' : 'Place Order';
  }

  // Initial calculation
  recalcTotal();

})();
