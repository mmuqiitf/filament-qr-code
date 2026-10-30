/** Identical announcements inside this window are dropped (a code held under the camera). */
export const REPEAT_WINDOW_MS = 3000;

/** Laravel-style `:name` replacement, longest name first so `:step` never eats `:stepper`. */
export function formatMessage(template, replacements = {}) {
    return Object.keys(replacements)
        .sort((a, b) => b.length - a.length)
        .reduce((message, name) => message.split(`:${name}`).join(String(replacements[name])), String(template ?? ''));
}

/**
 * Messages that arrive within this window of the last one are coalesced: only
 * the newest is written when the window ends, so rapid scans never queue up
 * a backlog of speech in the screen reader.
 */
export const COALESCE_WINDOW_MS = 500;

/**
 * State for the visually hidden polite live region (components/announcer).
 * Spread into an Alpine component; `announcement` is the region's text, and
 * each write replaces it rather than appending.
 */
export function announcerMixin() {
    return {
        announcement: '',
        lastAnnouncedAt: 0,
        pendingAnnouncement: null,
        pendingAnnouncementTimer: null,
        announcementTicket: 0,

        announce(message) {
            if (!message) return false;

            const now = Date.now();
            const latest = this.pendingAnnouncement ?? this.announcement;

            if (message === latest && now - this.lastAnnouncedAt < REPEAT_WINDOW_MS) return false;

            if (this.pendingAnnouncementTimer === null && now - this.lastAnnouncedAt >= COALESCE_WINDOW_MS) {
                this.writeAnnouncement(message);

                return true;
            }

            // Inside the window: keep only the newest message for the trailing write.
            this.pendingAnnouncement = message;
            this.pendingAnnouncementTimer ??= setTimeout(() => {
                const pending = this.pendingAnnouncement;
                this.pendingAnnouncement = null;
                this.pendingAnnouncementTimer = null;

                if (pending) this.writeAnnouncement(pending);
            }, Math.max(0, this.lastAnnouncedAt + COALESCE_WINDOW_MS - now));

            return true;
        },

        writeAnnouncement(message) {
            this.lastAnnouncedAt = Date.now();

            if (message !== this.announcement) {
                this.announcement = message;

                return;
            }

            // Same text again later: clear first so the region changes and is read again.
            this.announcement = '';
            setTimeout(() => {
                this.announcement = message;
            }, 100);
        },

        /**
         * Announce a scan with the payload's settings: nothing when
         * announcements are off, the server's announceUsing() text when set
         * (null keeps `fallback`), else `fallback`. Only the newest scan's
         * reply is used, so slow replies cannot read out stale scans.
         */
        announceScan(options, { code, accepted, reason = null, field = null, fallback }) {
            if (options?.announcements === false) return Promise.resolve(false);

            const wire = this.$wire;
            if (!options?.customAnnouncements || !options.componentKey || !wire?.callSchemaComponentMethod) {
                return Promise.resolve(this.announce(fallback));
            }

            const ticket = ++this.announcementTicket;

            return Promise.resolve()
                .then(() => wire.callSchemaComponentMethod(options.componentKey, 'announceScan', { code, accepted, reason, field }))
                .catch(() => null)
                .then((text) => (ticket === this.announcementTicket ? this.announce(text || fallback) : false));
        },
    };
}
