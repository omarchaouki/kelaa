/*
 * Cookie consent. Only essential cookies (session, CSRF, language) are used
 * by default; anything else (future audience measurement) must check
 * window.cookieConsent.allows('analytics') first, which is false until the
 * visitor accepts.
 */
const COOKIE = 'cookie_consent';
const MAX_AGE = 60 * 60 * 24 * 180; // 6 months, then we ask again

function read() {
    const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE}=(accepted|rejected)`));

    return match ? match[1] : null;
}

function write(value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

window.cookieConsent = {
    allows: (purpose) => purpose === 'essential' || read() === 'accepted',
};

export default function cookieConsent() {
    return {
        open: false,

        init() {
            this.open = read() === null;
        },

        choose(accepted) {
            write(accepted ? 'accepted' : 'rejected');
            this.open = false;
            window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: { accepted } }));
        },

        reopen() {
            this.open = true;
        },
    };
}
