// Mobile menu toggle
function toggleMobileMenu() {
  var menu = document.getElementById('mobile-menu');
  if (menu) menu.classList.toggle('active');
}

// Header hide/show on scroll
var lastScrollTop = 0;
var header = null;
document.addEventListener('DOMContentLoaded', function() {
  header = document.getElementById('header');
});
window.addEventListener('scroll', function() {
  if (!header) return;
  var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  if (scrollTop > lastScrollTop) {
    header.classList.add('hidden');
  } else {
    header.classList.remove('hidden');
  }
  lastScrollTop = scrollTop;
});

// Fade-in on scroll
var faders = document.querySelectorAll('.fade-in');
var appearOptions = { threshold: 0.5 };
var appearOnScroll = new IntersectionObserver(function(entries, observer) {
  entries.forEach(function(entry) {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('visible');
    observer.unobserve(entry.target);
  });
}, appearOptions);

faders.forEach(function(fader) { appearOnScroll.observe(fader); });

// Initialize Swiper if present
document.addEventListener('DOMContentLoaded', function() {
  if (window.Swiper) {
    var carousels = document.querySelectorAll('.swiper');
    carousels.forEach(function(el) {
      new Swiper(el, {
        loop: true,
        autoplay: { delay: 3000, disableOnInteraction: false },
        centeredSlides: false,
        spaceBetween: 20,
        slidesPerView: 1,
        breakpoints: {
          640: { slidesPerView: 2, spaceBetween: 16 },
          1024: { slidesPerView: 3, spaceBetween: 20 },
          1400: { slidesPerView: 4, spaceBetween: 24 }
        },
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev')
        }
      });
    });
  }

  // In-page navigation embedding
  var embedContainer = document.getElementById('embedded');
  function loadEmbedded(url) {
    if (!embedContainer) return;
    fetch(url)
      .then(function(r){ return r.text(); })
      .then(function(html){
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        // pick the first .section from the page
        var section = doc.querySelector('.section');
        embedContainer.innerHTML = section ? section.outerHTML : '<div class="card">Conținut indisponibil</div>';
        // re-init swiper for embedded content if present
        if (window.Swiper) {
          var carousels = embedContainer.querySelectorAll('.swiper');
          carousels.forEach(function(el) {
            new Swiper(el, {
              loop: true,
              autoplay: { delay: 3000, disableOnInteraction: false },
              centeredSlides: false,
              spaceBetween: 20,
              slidesPerView: 1,
              breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 16 },
                1024: { slidesPerView: 3, spaceBetween: 20 },
                1400: { slidesPerView: 4, spaceBetween: 24 }
              },
              pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
              navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev')
              }
            });
          });
        }
      })
      .catch(function(){ embedContainer.innerHTML = '<div class="card">Eroare la încărcare</div>'; });
  }

  // intercept header nav links with data-embed
  document.querySelectorAll('[data-embed]')
    .forEach(function(a){
      a.addEventListener('click', function(e){
        e.preventDefault();
        var href = a.getAttribute('href');
        loadEmbedded(href);
      });
    });

  // Auto-load default embedded section if requested
  var defaultEmbed = document.querySelector('[data-embed-default]');
  if (defaultEmbed) {
    loadEmbedded(defaultEmbed.getAttribute('href'));
  }
});
