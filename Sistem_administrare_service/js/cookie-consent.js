
document.addEventListener('DOMContentLoaded', () => {
    const COOKIE_CONSENT_KEY = 'serviceflow_cookie_consent';
    const banner = document.getElementById('cookie-consent-banner');
    const acceptButton = document.getElementById('cookie-consent-accept');

    if (!banner || !acceptButton) {
        return;
    }

    // Functie pentru a verifica dacă consimțământul a fost deja dat
    const hasConsent = () => {
        return document.cookie.split(';').some((item) => item.trim().startsWith(`${COOKIE_CONSENT_KEY}=`));
    };

    // Afișează banner-ul dacă nu există consimțământ
    if (!hasConsent()) {
        banner.setAttribute('aria-hidden', 'false');
        banner.classList.add('show');
    }

    // La click pe butonul de accept
    acceptButton.addEventListener('click', () => {
        // Setează un cookie care expiră într-un an
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = `${COOKIE_CONSENT_KEY}=true; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;

        // Ascunde banner-ul cu o tranziție
        banner.classList.remove('show');
        banner.setAttribute('aria-hidden', 'true');
    });
});
