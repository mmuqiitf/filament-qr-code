@php
    $fields = $getFields();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $burstThresholdMs = $getBurstThresholdMs();
    $preventSubmit = $shouldPreventFormSubmit();
    $autoFocusNext = $isAutoFocusNext();
@endphp

<div
    x-data="{
        registeredFields: @js($fields),
        wedgeHandler: null,

        init() {
            this.wedgeHandler = FilamentQrCode.createWedgeHandler({
                burstThresholdMs: @js($burstThresholdMs),
                preventFormSubmit: @js($preventSubmit),
                sound: @js($hasSound),
                vibrate: @js($hasVibration),
                onScan: (scannedValue) => {
                    this.handleGlobalScan(scannedValue);
                }
            });

            window.addEventListener('keydown', (e) => {
                this.wedgeHandler.handleKeyDown(e);
            });
        },

        handleGlobalScan(scannedValue) {
            let targetInput = null;
            let targetFieldName = null;
            const active = document.activeElement;

            // 1. If currently focused element is an input, use it
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) {
                targetInput = active;
                targetFieldName = active.getAttribute('name') || active.closest('[data-field-name]')?.getAttribute('data-field-name');
            }

            // 2. Otherwise find the first empty registered field
            if (!targetInput && this.registeredFields.length > 0) {
                for (const fieldName of this.registeredFields) {
                    const el = document.querySelector(`[data-field-name=\"${fieldName}\"] input, input[name=\"${fieldName}\"], #${fieldName}`);
                    if (el && !el.value) {
                        targetInput = el;
                        targetFieldName = fieldName;
                        break;
                    }
                }
            }

            // 3. Fallback: find first empty text input on page
            if (!targetInput) {
                const inputs = document.querySelectorAll('form input[type=\"text\"]:not([disabled]):not([readonly])');
                for (const input of inputs) {
                    if (!input.value) {
                        targetInput = input;
                        targetFieldName = input.getAttribute('name') || input.closest('[data-field-name]')?.getAttribute('data-field-name');
                        break;
                    }
                }
            }

            if (targetInput) {
                targetInput.focus();
                targetInput.value = scannedValue;
                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));

                // Dispatch global event
                window.dispatchEvent(new CustomEvent('qr-wedge-scanned', {
                    detail: {
                        value: scannedValue,
                        field: targetFieldName,
                    }
                }));

                // Auto-advance to next empty field
                if (@js($autoFocusNext)) {
                    this.$nextTick(() => {
                        this.advanceToNextEmpty(targetInput);
                    });
                }
            }
        },

        advanceToNextEmpty(currentInput) {
            const allInputs = Array.from(document.querySelectorAll('form input[type=\"text\"]:not([disabled]):not([readonly])'));
            const currentIndex = allInputs.indexOf(currentInput);

            if (currentIndex !== -1 && currentIndex < allInputs.length - 1) {
                const nextInput = allInputs[currentIndex + 1];
                if (nextInput) {
                    nextInput.focus();
                    if (typeof nextInput.select === 'function') {
                        nextInput.select();
                    }
                }
            }
        }
    }"
    class="hidden"
></div>
