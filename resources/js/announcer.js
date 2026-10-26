/** Identical announcements inside this window are dropped (a code held under the camera). */
export const REPEAT_WINDOW_MS = 3000;

/** Laravel-style `:name` replacement, longest name first so `:step` never eats `:stepper`. */
export function formatMessage(template, replacements = {}) {
    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce((message, name) => message.split(`:${name}`).join(String(replacements[name])), String(template ?? ''));
}

/**
 * State for the visually hidden polite live region (components/announcer).
 * Spread into an Alpine component; `announcement` is the region's text.
 */
export function announcerMixin() {
    return {
        announcement: '',
        lastAnnouncedAt: 0,

        announce(message) {
            if (!message) return false;

            const now = Date.now();

            if (message === this.announcement) {
                if (now - this.lastAnnouncedAt < REPEAT_WINDOW_MS) return false;

                // Same text again later: clear first so the region changes and is read again.
                this.announcement = '';
                this.lastAnnouncedAt = now;
                setTimeout(() => {
                    this.announcement = message;
                }, 100);

                return true;
            }

            this.announcement = message;
            this.lastAnnouncedAt = now;

            return true;
        },
    };
}
