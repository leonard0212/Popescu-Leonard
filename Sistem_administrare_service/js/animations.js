document.addEventListener('DOMContentLoaded', () => {

    // --- Efect de Particule la Mișcarea Mouse-ului ---
    const canvas = document.createElement('canvas');
    canvas.id = 'particle-canvas';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    let particlesArray = [];
    const mouse = { x: null, y: null };

    const setCanvasSize = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };
    setCanvasSize();

    window.addEventListener('resize', setCanvasSize);

    window.addEventListener('mousemove', (event) => {
        mouse.x = event.x;
        mouse.y = event.y;
        // Creăm un mic "burst" de particule la mișcare
        for (let i = 0; i < 2; i++) {
            particlesArray.push(new Particle());
        }
    });

    class Particle {
        constructor() {
            this.x = mouse.x;
            this.y = mouse.y;
            this.size = Math.random() * 5 + 1;
            this.speedX = Math.random() * 3 - 1.5;
            this.speedY = Math.random() * 3 - 1.5;
            this.color = `hsla(199, 100%, 50%, ${Math.random() * 0.5 + 0.3})`; // Nuanțe de albastru
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            if (this.size > 0.2) this.size -= 0.1;
        }
        draw() {
            ctx.fillStyle = this.color;
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function handleParticles() {
        for (let i = 0; i < particlesArray.length; i++) {
            particlesArray[i].update();
            particlesArray[i].draw();
            if (particlesArray[i].size <= 0.3) {
                particlesArray.splice(i, 1);
                i--;
            }
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        handleParticles();
        requestAnimationFrame(animate);
    }
    animate();

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
