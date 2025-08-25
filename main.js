// Mobile Menu Toggle
const menuBtn = document.createElement('button');
menuBtn.innerHTML = '☰';
menuBtn.setAttribute('aria-label', 'Open menu');
menuBtn.classList.add('menu-toggle');
document.body.prepend(menuBtn);

const mobileMenu = document.getElementById('mobile-menu');
const closeBtn = document.getElementById('close-menu');

menuBtn.addEventListener('click', () => {
  mobileMenu.classList.add('active');
});

closeBtn.addEventListener('click', () => {
  mobileMenu.classList.remove('active');
});

// Close menu on link click
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    mobileMenu.classList.remove('active');
  });
});

// Sticky Header
const header = document.querySelector('.header');
window.addEventListener('scroll', () => {
  if (window.scrollY > 100) {
    header.classList.add('visible');
  } else {
    header.classList.remove('visible');
  }
});

// Scroll Animations
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('is-visible');
    }
  });
}, observerOptions);

document.querySelectorAll('.fade-left, .fade-right, .fade-scale').forEach(el => {
  observer.observe(el);
});

// Prevent overscroll on mobile
document.querySelector('.carousel').addEventListener('touchmove', e => {
  e.preventDefault();
}, { passive: false });

class ParticleOrb {
  constructor() {
    this.container = document.getElementById('particle-orb');
    this.particles = [];
    this.mouse = { x: 0, y: 0 };
    this.gyro = { x: 0, y: 0 };
    this.isMobile = window.innerWidth <= 768;

    this.init();
    this.bindEvents();
    this.animate();
  }

  init() {
    const count = this.isMobile ? 15 : 30;
    for (let i = 0; i < count; i++) {
      const particle = document.createElement('div');
      particle.classList.add('particle');
      this.container.appendChild(particle);
      this.particles.push({
        el: particle,
        x: 0,
        y: 0,
        size: Math.random() * 4 + 1,
        speed: Math.random() * 0.5 + 0.2,
        angle: Math.random() * Math.PI * 2
      });
    }
  }

  bindEvents() {
    if (!this.isMobile) {
      window.addEventListener('mousemove', (e) => {
        this.mouse.x = e.clientX - window.innerWidth / 2;
        this.mouse.y = e.clientY - window.innerHeight / 2;
      });
    } else {
      window.addEventListener('deviceorientation', (e) => {
        if (e.beta && e.gamma) {
          this.gyro.x = e.gamma / 10;
          this.gyro.y = e.beta / 10;
        }
      });
    }

    window.addEventListener('scroll', () => {
      const hero = document.querySelector('.hero');
      const rect = hero.getBoundingClientRect();
      if (rect.top + rect.height / 2 < window.innerHeight) {
        this.dissolve();
      }
    });
  }

  animate() {
    this.particles.forEach(p => {
      p.angle += p.speed * 0.05;
      const radius = 80 + Math.sin(Date.now() * 0.001) * 10;

      let dx = Math.cos(p.angle) * radius;
      let dy = Math.sin(p.angle) * radius;

      if (!this.isMobile && this.mouse.x) {
        dx += this.mouse.x * 0.05;
        dy += this.mouse.y * 0.05;
      } else if (this.isMobile) {
        dx += this.gyro.x * 10;
        dy += this.gyro.y * 10;
      }

      p.x += (dx - p.x) * 0.02;
      p.y += (dy - p.y) * 0.02;

      p.el.style.transform = `translate(${p.x}px, ${p.y}px) scale(${p.size})`;
      p.el.style.opacity = 0.7 + Math.sin(Date.now() * 0.002) * 0.3;
      p.el.style.boxShadow = `0 0 ${p.size * 10}px rgba(157, 114, 255, 0.6)`;
    });

    requestAnimationFrame(() => this.animate());
  }

  dissolve() {
    this.particles.forEach((p, i) => {
      setTimeout(() => {
        p.el.style.transition = 'all 1.5s ease-out';
        p.el.style.opacity = '0';
        p.el.style.transform += ` translateY(${window.innerHeight * 0.3}px) rotate(${Math.random() * 360}deg)`;
      }, i * 50);
    });
  }
}

// Initialize on load
window.addEventListener('DOMContentLoaded', () => {
  new ParticleOrb();
});