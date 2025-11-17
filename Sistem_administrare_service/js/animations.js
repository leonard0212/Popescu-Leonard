document.addEventListener('DOMContentLoaded', () => {

    // --- Efect de Spotlight la Mișcarea Mouse-ului ---
    const spotlight = document.createElement('div');
    spotlight.classList.add('spotlight-effect');
    document.body.appendChild(spotlight);

    document.addEventListener('mousemove', (e) => {
        // Folosim `requestAnimationFrame` pentru o performanță mai bună
        requestAnimationFrame(() => {
            spotlight.style.transform = `translate(${e.clientX}px, ${e.clientY}px)`;
        });
    });

    // --- Animații la Derulare (Scroll) ---
    const animatedElements = document.querySelectorAll('.animate-on-scroll');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                // Opțional: oprește observarea după ce animația a avut loc
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1 // Elementul devine vizibil când 10% din el este în viewport
    });

    animatedElements.forEach(element => {
        observer.observe(element);
    });

});
