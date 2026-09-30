/**
 * Contact button tracking with navigator.sendBeacon: never delays the
 * WhatsApp / call / directions link the visitor clicked.
 */
export function track(businessId, type, source = 'organic', placementId = null) {
    try {
        const endpoint = document.querySelector('meta[name="contact-events-endpoint"]')?.content;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!endpoint || !token) {
            return;
        }

        const data = new FormData();
        data.append('_token', token);
        data.append('business', String(businessId));
        data.append('type', type);
        data.append('source', source);

        if (placementId) {
            data.append('placement', String(placementId));
        }

        navigator.sendBeacon(endpoint, data);
    } catch {
        // Statistics must never break the page.
    }
}

/**
 * Native share sheet on phones, clipboard copy elsewhere.
 */
export async function share(title, url, copiedMessage) {
    try {
        if (navigator.share) {
            await navigator.share({ title, url });

            return;
        }

        await navigator.clipboard.writeText(url);
        window.alert(copiedMessage);
    } catch {
        // User cancelled the share sheet.
    }
}
