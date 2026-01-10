
function handleCookieConsent() {
    console.log("Cookie consent script started.");

    const banner = document.getElementById('cookie-consent-banner');
    const acceptButton = document.getElementById('cookie-consent-accept');

    if (!banner || !acceptButton) {
        console.error("Banner or accept button not found.");
        return;
    }

    const COOKIE_CONSENT_KEY = 'serviceflow_cookie_consent';

    // Check if consent has already been given (cookie or localStorage)
    const hasConsent = document.cookie.includes(`${COOKIE_CONSENT_KEY}=true`) || localStorage.getItem(COOKIE_CONSENT_KEY) === 'true';

    if (hasConsent) {
        console.log("Consent already given. Hiding banner completely.");
        banner.style.display = 'none'; // Hide immediately
        return;
    }

    // Show banner if no consent found
    console.log("No consent found. Showing banner.");
    banner.classList.add('show');
    banner.setAttribute('aria-hidden', 'false');

    function onAccept() {
        console.log("Accept button clicked.");

        // Set cookie
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = `${COOKIE_CONSENT_KEY}=true; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;

        // Set localStorage as backup
        localStorage.setItem(COOKIE_CONSENT_KEY, 'true');

        console.log("Cookie and localStorage set.");

        // Start hide transition
        banner.classList.remove('show');
        banner.setAttribute('aria-hidden', 'true');
        console.log("Banner hiding transition started.");

        // Listen for transition end
        banner.addEventListener('transitionend', () => {
            banner.style.display = 'none';
            console.log("Banner hidden completely after transition.");
        }, { once: true });

        acceptButton.removeEventListener('click', onAccept);
    }

    acceptButton.addEventListener('click', onAccept);
    console.log("Click event listener added.");
}

// Run script after DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', handleCookieConsent);
} else {
    handleCookieConsent();
}
