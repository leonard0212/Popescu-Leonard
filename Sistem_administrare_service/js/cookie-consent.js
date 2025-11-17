
function handleCookieConsent() {
    console.log("Cookie consent script started.");

    const banner = document.getElementById('cookie-consent-banner');
    const acceptButton = document.getElementById('cookie-consent-accept');

    if (!banner || !acceptButton) {
        console.error("Banner or accept button not found.");
        return;
    }

    const COOKIE_CONSENT_KEY = 'serviceflow_cookie_consent';

    // Verifică dacă consimțământul a fost deja dat
    if (document.cookie.includes(`${COOKIE_CONSENT_KEY}=true`)) {
        console.log("Consent already given. Hiding banner completely.");
        banner.style.display = 'none'; // Ascunde-l direct
        return;
    }

    // Afișează banner-ul dacă nu există consimțământ
    console.log("No consent found. Showing banner.");
    banner.classList.add('show');
    banner.setAttribute('aria-hidden', 'false');

    function onAccept() {
        console.log("Accept button clicked.");

        // Setează cookie-ul
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = `${COOKIE_CONSENT_KEY}=true; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
        console.log("Cookie set.");

        // Începe tranziția de ascundere
        banner.classList.remove('show');
        banner.setAttribute('aria-hidden', 'true');
        console.log("Banner hiding transition started.");

        // Ascultă evenimentul de finalizare a tranziției
        banner.addEventListener('transitionend', () => {
            banner.style.display = 'none';
            console.log("Banner hidden completely after transition.");
        }, { once: true }); // Asigură-te că rulează o singură dată

        acceptButton.removeEventListener('click', onAccept);
    }

    acceptButton.addEventListener('click', onAccept);
    console.log("Click event listener added.");
}

// Rulează scriptul după ce DOM-ul este complet încărcat
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', handleCookieConsent);
} else {
    handleCookieConsent();
}
