@extends('layouts.app')

@section('title', 'Email Verification - LMLinga')

@section('body')
    @php
        /*
         * UI-only demo masked email for the verification preview.
         * Replace with the resident's submitted email when backend is wired.
         */
        $maskedEmail = 'jo******@gmail.com';
    @endphp

    <div
        class="lml-chatbot-sms-verify"
        data-lml-sms-verify
        data-status-url="{{ route('chatbot.household.verification.status') }}"
        data-alternative-url="{{ route('chatbot.household.verification.sms') }}"
        data-resend-success-message="New verification email sent."
        data-otp-seconds="179"
    >
        <div class="lml-chatbot-sms-verify__inner">
            <header class="lml-chatbot-sms-verify__header">
                <a
                    href="{{ route('chatbot.household.verification.sms') }}"
                    class="lml-chatbot-sms-verify__back lml-focus-ring"
                >
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    <span>Back</span>
                </a>
            </header>

            <main class="lml-chatbot-sms-verify__main" id="main-content">
                <section
                    class="lml-chatbot-sms-verify__card lml-surface lml-surface--elevated"
                    aria-labelledby="email-verify-heading"
                >
                    <div class="lml-chatbot-sms-verify__hero">
                        <span class="lml-chatbot-sms-verify__hero-icon" aria-hidden="true">
                            <i class="bi bi-envelope-check-fill"></i>
                        </span>
                        <h1 id="email-verify-heading" class="lml-chatbot-sms-verify__title">
                            Email Verification
                        </h1>
                        <p class="lml-chatbot-sms-verify__intro">
                            We've sent a 6-digit code to your email address
                            <span class="lml-chatbot-sms-verify__masked">{{ $maskedEmail }}</span>.
                        </p>
                        <p class="lml-chatbot-sms-verify__intro lml-chatbot-sms-verify__intro--secondary">
                            Enter the 6-digit code below to verify your identity and access the household record.
                        </p>
                    </div>

                    {{--
                        UI-phase form: method stays POST with CSRF for secure markup.
                        Submission is intercepted by client-side validation until backend email verification is wired.
                        Do not use method="get" (would expose the code in the URL).
                    --}}
                    <form
                        class="lml-chatbot-sms-verify__form"
                        action="{{ route('chatbot.household.verification.email') }}"
                        method="post"
                        novalidate
                        data-lml-sms-form
                    >
                        @csrf

                        <fieldset class="lml-chatbot-sms-verify__otp-fieldset">
                            <legend class="visually-hidden">
                                Enter the 6-digit verification code
                            </legend>
                            <div
                                class="lml-chatbot-sms-verify__otp"
                                data-lml-otp-group
                            >
                                @for ($i = 0; $i < 6; $i++)
                                    <label class="visually-hidden" for="email-otp-{{ $i }}">
                                        Digit {{ $i + 1 }} of 6
                                    </label>
                                    <input
                                        id="email-otp-{{ $i }}"
                                        class="lml-chatbot-sms-verify__otp-input lml-focus-ring"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                        maxlength="1"
                                        pattern="[0-9]"
                                        data-lml-otp-digit
                                        data-otp-index="{{ $i }}"
                                        aria-invalid="false"
                                        aria-describedby="email-otp-error"
                                    >
                                @endfor
                            </div>
                            <p
                                id="email-otp-error"
                                class="lml-chatbot-sms-verify__error"
                                data-lml-otp-error
                                hidden
                            ></p>
                        </fieldset>

                        <button
                            type="submit"
                            class="lml-chatbot-sms-verify__verify-btn lml-focus-ring"
                            data-lml-otp-verify
                            aria-busy="false"
                            disabled
                        >
                            Verify
                        </button>

                        <p
                            class="lml-chatbot-sms-verify__timer"
                            data-lml-otp-timer
                        >
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            <span data-lml-otp-timer-text>
                                The code will expire in
                                <strong data-lml-otp-timer-value>02:59</strong>
                            </span>
                        </p>
                        <span
                            class="visually-hidden"
                            data-lml-otp-announcement
                            aria-live="polite"
                            aria-atomic="true"
                        ></span>

                        <p class="lml-chatbot-sms-verify__resend">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span>Didn't receive a code?</span>
                            <button
                                type="button"
                                class="lml-chatbot-sms-verify__resend-btn lml-focus-ring"
                                data-lml-otp-resend
                                disabled
                            >
                                Resend Email
                            </button>
                        </p>

                        <div class="lml-chatbot-sms-verify__divider">
                            <span class="lml-chatbot-sms-verify__divider-line" aria-hidden="true"></span>
                            <span class="lml-chatbot-sms-verify__divider-label">OR</span>
                            <span class="lml-chatbot-sms-verify__divider-line" aria-hidden="true"></span>
                        </div>

                        <button
                            type="button"
                            class="lml-chatbot-sms-verify__alt-btn lml-focus-ring"
                            data-lml-otp-alternative
                        >
                            <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
                            <span>Try Other Way (Send via SMS)</span>
                        </button>
                    </form>
                </section>
            </main>
        </div>

        <div
            class="lml-chatbot-sms-verify__toast"
            data-lml-sms-toast
            role="status"
            aria-live="polite"
            hidden
        ></div>
    </div>
@endsection
