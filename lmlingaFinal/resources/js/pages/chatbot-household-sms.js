/**
 * Resident AI Chatbot — SMS Verification OTP UI (UI-only).
 * No real SMS delivery or backend OTP validation.
 */

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function formatCountdown(totalSeconds) {
    const safe = Math.max(0, totalSeconds);
    const minutes = String(Math.floor(safe / 60)).padStart(2, '0');
    const seconds = String(safe % 60).padStart(2, '0');
    return `${minutes}:${seconds}`;
}

function initSmsVerification(root) {
    const form = root.querySelector('[data-lml-sms-form]');
    const digits = Array.from(root.querySelectorAll('[data-lml-otp-digit]'));
    const errorEl = root.querySelector('[data-lml-otp-error]');
    const timerEl = root.querySelector('[data-lml-otp-timer]');
    const timerTextEl = root.querySelector('[data-lml-otp-timer-text]');
    const announcementEl = root.querySelector('[data-lml-otp-announcement]');
    const verifyBtn = root.querySelector('[data-lml-otp-verify]');
    const resendBtn = root.querySelector('[data-lml-otp-resend]');
    const alternativeBtn = root.querySelector('[data-lml-otp-alternative]');
    const toastEl = root.querySelector('[data-lml-sms-toast]');
    const statusUrl = root.dataset.statusUrl;
    const alternativeUrl = root.dataset.alternativeUrl;
    const resendSuccessMessage = root.dataset.resendSuccessMessage || 'New OTP sent.';

    if (!form || !verifyBtn || digits.length !== 6) {
        return;
    }

    const initialSeconds = Number.parseInt(root.dataset.otpSeconds || '179', 10);
    let remainingSeconds = Number.isFinite(initialSeconds) ? initialSeconds : 179;
    let timerId = null;
    let toastTimerId = null;
    let verifying = false;
    let hasHandledExpiry = false;
    const announcedThresholds = new Set();

    const countdownAnnouncements = new Map([
        [60, 'The verification code will expire in 1 minute.'],
        [30, '30 seconds remaining.'],
        [10, '10 seconds remaining.'],
        [0, 'The verification code has expired. Select Resend OTP to request a new code.'],
    ]);

    function showToast(message) {
        if (!toastEl) {
            return;
        }
        toastEl.hidden = false;
        toastEl.textContent = message;
        window.clearTimeout(toastTimerId);
        toastTimerId = window.setTimeout(
            () => {
                toastEl.hidden = true;
                toastEl.textContent = '';
            },
            prefersReducedMotion() ? 1800 : 2800
        );
    }

    function setError(message, invalidDigits = []) {
        if (!errorEl) {
            return;
        }
        if (!message) {
            errorEl.hidden = true;
            errorEl.textContent = '';
            digits.forEach((input) => {
                input.setAttribute('aria-invalid', 'false');
            });
            return;
        }
        errorEl.hidden = false;
        errorEl.textContent = message;
        digits.forEach((input) => {
            input.setAttribute('aria-invalid', invalidDigits.includes(input) ? 'true' : 'false');
        });
    }

    function getCode() {
        return digits.map((input) => input.value).join('');
    }

    function syncVerifyButtonState() {
        const hasValidCode = /^\d{6}$/.test(getCode());
        const canVerify = hasValidCode && remainingSeconds > 0 && !verifying;
        verifyBtn.disabled = !canVerify;

        if (!verifying) {
            verifyBtn.textContent = 'Verify';
            verifyBtn.setAttribute('aria-busy', 'false');
        }
    }

    function clearDigits() {
        digits.forEach((input) => {
            input.value = '';
            input.disabled = false;
        });
        setError('');
        syncVerifyButtonState();
        digits[0]?.focus();
    }

    function setInputsDisabled(disabled) {
        digits.forEach((input) => {
            input.disabled = disabled;
        });
    }

    function announceCountdownThreshold() {
        if (!announcementEl || announcedThresholds.has(remainingSeconds)) {
            return;
        }

        const message = countdownAnnouncements.get(remainingSeconds);
        if (!message) {
            return;
        }

        announcedThresholds.add(remainingSeconds);
        announcementEl.textContent = message;
    }

    function updateTimerUi() {
        if (!timerEl || !timerTextEl) {
            return;
        }

        announceCountdownThreshold();

        if (remainingSeconds <= 0) {
            timerEl.classList.add('is-expired');
            timerTextEl.innerHTML = '<strong>Code expired</strong>';
            if (resendBtn) {
                resendBtn.disabled = false;
            }
            setInputsDisabled(true);
            syncVerifyButtonState();

            if (!hasHandledExpiry) {
                hasHandledExpiry = true;
                window.requestAnimationFrame(() => {
                    resendBtn?.focus();
                });
            }
            return;
        }

        timerEl.classList.remove('is-expired');
        timerTextEl.innerHTML =
            `The code will expire in <strong data-lml-otp-timer-value>${formatCountdown(remainingSeconds)}</strong>`;
        if (resendBtn) {
            resendBtn.disabled = true;
        }
        setInputsDisabled(false);
        syncVerifyButtonState();
    }

    function stopTimer() {
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    }

    function startTimer(seconds) {
        stopTimer();
        remainingSeconds = seconds;
        hasHandledExpiry = false;
        announcedThresholds.clear();
        if (announcementEl) {
            announcementEl.textContent = '';
        }
        updateTimerUi();
        timerId = window.setInterval(() => {
            remainingSeconds -= 1;
            updateTimerUi();
            if (remainingSeconds <= 0) {
                stopTimer();
            }
        }, 1000);
    }

    function focusDigit(index) {
        const target = digits[index];
        if (!target || target.disabled) {
            return;
        }
        target.focus();
        target.select();
    }

    function tryVerify() {
        if (verifying || remainingSeconds <= 0) {
            return;
        }

        const code = getCode();
        if (!/^\d{6}$/.test(code)) {
            const invalidDigits = digits.filter((input) => !/^\d$/.test(input.value));
            setError('Please enter the complete 6-digit verification code.', invalidDigits);
            const firstInvalid = digits.indexOf(invalidDigits[0]);
            focusDigit(firstInvalid === -1 ? 0 : firstInvalid);
            syncVerifyButtonState();
            return;
        }

        setError('');
        verifying = true;
        stopTimer();
        setInputsDisabled(true);
        verifyBtn.textContent = 'Verifying…';
        verifyBtn.disabled = true;
        verifyBtn.setAttribute('aria-busy', 'true');
        if (resendBtn) {
            resendBtn.disabled = true;
        }

        /* Mock verification success — navigate to status placeholder. */
        const delay = prefersReducedMotion() ? 120 : 450;
        window.setTimeout(() => {
            if (statusUrl) {
                window.location.assign(statusUrl);
                return;
            }
            verifying = false;
            setInputsDisabled(false);
            syncVerifyButtonState();
            showToast('OTP verified (demo).');
        }, delay);
    }

    digits.forEach((input, index) => {
        input.addEventListener('input', (event) => {
            if (remainingSeconds <= 0 || verifying) {
                return;
            }

            const value = event.target.value.replace(/\D/g, '');
            event.target.value = value.slice(-1);
            setError('');

            if (event.target.value && index < digits.length - 1) {
                focusDigit(index + 1);
            }
            syncVerifyButtonState();
        });

        input.addEventListener('keydown', (event) => {
            if (verifying) {
                return;
            }

            if (event.key === 'Backspace') {
                event.preventDefault();
                if (input.value === '' && index > 0) {
                    digits[index - 1].value = '';
                    focusDigit(index - 1);
                } else {
                    input.value = '';
                }
                setError('');
                syncVerifyButtonState();
                return;
            }

            if (event.key === 'Delete') {
                event.preventDefault();
                input.value = '';
                setError('');
                syncVerifyButtonState();
                return;
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                focusDigit(index - 1);
                return;
            }

            if (event.key === 'ArrowRight' && index < digits.length - 1) {
                event.preventDefault();
                focusDigit(index + 1);
                return;
            }

            if (event.key.length === 1 && !/\d/.test(event.key) && !event.ctrlKey && !event.metaKey) {
                event.preventDefault();
            }
        });

        input.addEventListener('paste', (event) => {
            if (remainingSeconds <= 0 || verifying) {
                event.preventDefault();
                return;
            }

            event.preventDefault();
            const pasted = (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 6);

            if (!pasted) {
                return;
            }

            digits.forEach((digit, i) => {
                digit.value = pasted[i] || '';
            });
            setError('');
            syncVerifyButtonState();

            if (pasted.length === 6) {
                focusDigit(5);
            } else {
                focusDigit(pasted.length);
            }
        });

        input.addEventListener('focus', () => {
            input.select();
        });
    });

    if (resendBtn) {
        resendBtn.addEventListener('click', () => {
            if (resendBtn.disabled || verifying) {
                return;
            }
            verifying = false;
            clearDigits();
            startTimer(initialSeconds);
            showToast(resendSuccessMessage);
        });
    }

    if (alternativeBtn) {
        alternativeBtn.addEventListener('click', () => {
            if (alternativeUrl) {
                window.location.assign(alternativeUrl);
                return;
            }
            showToast('Alternative verification is not available yet.');
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        tryVerify();
    });

    startTimer(remainingSeconds);
    window.requestAnimationFrame(() => {
        focusDigit(0);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-sms-verify]').forEach((root) => {
        initSmsVerification(root);
    });
});
