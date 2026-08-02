{{--
    Notification details modal — UI-only preview for verified resident schedules.
    Populated via chatbot-main.js from sidebar notification row data attributes.
--}}
<div
    class="lml-chatbot-notification-modal"
    data-lml-notification-modal
    hidden
>
    <div
        class="lml-chatbot-notification-modal__backdrop"
        data-lml-notification-modal-backdrop
    ></div>

    <div
        class="lml-chatbot-notification-modal__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chatbot-notification-modal-title"
        aria-describedby="chatbot-notification-modal-reminder"
        tabindex="-1"
        data-lml-notification-modal-panel
    >
        <header class="lml-chatbot-notification-modal__header">
            <div class="lml-chatbot-notification-modal__header-text">
                <p class="lml-chatbot-notification-modal__eyebrow">
                    Upcoming Health Schedule
                </p>
                <h2
                    id="chatbot-notification-modal-title"
                    class="lml-chatbot-notification-modal__title"
                    data-lml-notification-modal-service
                ></h2>
            </div>
            <button
                type="button"
                class="lml-chatbot-notification-modal__close lml-focus-ring"
                data-lml-notification-modal-close
                aria-label="Close notification details"
            >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="lml-chatbot-notification-modal__body">
            <dl class="lml-chatbot-notification-modal__details">
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Service</dt>
                    <dd data-lml-notification-modal-detail="service"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Household Member</dt>
                    <dd data-lml-notification-modal-detail="member"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Relationship</dt>
                    <dd data-lml-notification-modal-detail="relationship"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Scheduled Date</dt>
                    <dd data-lml-notification-modal-detail="date"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Scheduled Time</dt>
                    <dd data-lml-notification-modal-detail="time"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Place</dt>
                    <dd data-lml-notification-modal-detail="place"></dd>
                </div>
                <div class="lml-chatbot-notification-modal__detail">
                    <dt>Status</dt>
                    <dd>
                        <span
                            class="lml-chatbot-notifications__status"
                            data-lml-notification-modal-detail="status"
                        ></span>
                    </dd>
                </div>
            </dl>

            <section
                class="lml-chatbot-notification-modal__reminder"
                aria-labelledby="chatbot-notification-reminder-heading"
            >
                <div class="lml-chatbot-notification-modal__reminder-heading">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <h3 id="chatbot-notification-reminder-heading">Schedule Reminder</h3>
                </div>
                <p
                    id="chatbot-notification-modal-reminder"
                    class="lml-chatbot-notification-modal__reminder-text"
                    data-lml-notification-modal-reminder
                ></p>
            </section>
        </div>

        <footer class="lml-chatbot-notification-modal__actions">
            <button
                type="button"
                class="lml-chatbot-notification-modal__btn lml-chatbot-notification-modal__btn--secondary lml-focus-ring"
                data-lml-notification-modal-dismiss
            >
                Close
            </button>
        </footer>
    </div>
</div>
