document.addEventListener('DOMContentLoaded', function() {

    // --- 1. HERO PARTICLE ANIMATION ---
    const canvas = document.getElementById('particle-canvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let particles = [];
        let mouse = { x: null, y: null, radius: 100 };
        let animationFrameId;
        let isScrolling = false;
        let scrollGravity = 0;

        function setCanvasSize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        class Particle {
            constructor(x, y, size, color, weight) {
                this.x = x;
                this.y = y;
                this.size = size;
                this.color = color;
                this.weight = weight;
                this.baseX = this.x;
                this.baseY = this.y;
                this.density = (Math.random() * 30) + 1;
                this.alpha = 1;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
                ctx.fillStyle = `rgba(157, 114, 255, ${this.alpha})`;
                ctx.fill();
            }

            update() {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                let forceDirectionX = dx / distance;
                let forceDirectionY = dy / distance;
                let maxDistance = mouse.radius;
                let force = (maxDistance - distance) / maxDistance;
                let directionX = (forceDirectionX * force * this.density);
                let directionY = (forceDirectionY * force * this.density);

                if (isScrolling) {
                    this.y += this.weight + scrollGravity;
                    this.alpha -= 0.005;
                } else {
                    if (distance < mouse.radius) {
                        this.x -= directionX;
                        this.y -= directionY;
                    } else {
                        if (this.x !== this.baseX) {
                            let dx = this.x - this.baseX;
                            this.x -= dx / 10;
                        }
                        if (this.y !== this.baseY) {
                            let dy = this.y - this.baseY;
                            this.y -= dy / 10;
                        }
                    }
                }
                this.draw();
            }
        }

        function initParticles() {
            particles = [];
            const particleCount = window.innerWidth > 768 ? 150 : 75;
            const radius = Math.min(canvas.width, canvas.height) / 4;
            for (let i = 0; i < particleCount; i++) {
                const angle = (i / particleCount) * Math.PI * 2;
                const r = Math.random() * radius;
                const x = canvas.width / 2 + Math.cos(angle) * r;
                const y = canvas.height / 2 + Math.sin(angle) * r;
                const size = Math.random() * 2.5 + 1;
                const weight = Math.random() * 1.5 + 0.5;
                particles.push(new Particle(x, y, size, 'var(--color-accent-primary)', weight));
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].update();
            }
            if (isScrolling) {
                scrollGravity += 0.05;
            }
            // Filter out faded particles
            particles = particles.filter(p => p.alpha > 0 && p.y < canvas.height + 10);

            if (particles.length > 0 || isScrolling) {
               animationFrameId = requestAnimationFrame(animate);
            } else {
                // stop animation if all particles are gone and not scrolling
                cancelAnimationFrame(animationFrameId);
            }
        }

        function startAnimation() {
            if (!animationFrameId) {
                animate();
            }
        }

        function stopAnimation() {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }

        // Event Listeners for Particle Animation
        window.addEventListener('resize', () => {
            setCanvasSize();
            initParticles();
        });

        canvas.addEventListener('mousemove', (event) => {
            mouse.x = event.x;
            mouse.y = event.y;
        });

        canvas.addEventListener('mouseleave', () => {
            mouse.x = null;
            mouse.y = null;
        });

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100 && !isScrolling) {
                isScrolling = true;
                startAnimation(); // Ensure animation is running to dissolve particles
            }
        });

        // Use IntersectionObserver to start/stop the animation to save performance
        const heroObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startAnimation();
                } else {
                    stopAnimation();
                }
            });
        }, { threshold: 0.1 });

        heroObserver.observe(canvas);

        // Initial setup
        setCanvasSize();
        initParticles();
    }


    // --- 2. NAVIGATION LOGIC ---
    const navToggle = document.querySelector('.nav-toggle');
    const body = document.body;
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-overlay a');

    navToggle.addEventListener('click', () => {
        const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', !isExpanded);
        body.classList.toggle('nav-open');
    });

    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            body.classList.remove('nav-open');
            navToggle.setAttribute('aria-expanded', 'false');
        });
    });

    const header = document.querySelector('.main-header');
    const heroSection = document.getElementById('hero');
    window.addEventListener('scroll', () => {
        if (window.scrollY > heroSection.offsetHeight - 80) {
            header.classList.add('sticky');
        } else {
            header.classList.remove('sticky');
        }
    });


    // --- 3. SCROLL-TRIGGERED ANIMATIONS ---
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Stagger animations for elements like cards
                const delay = entry.target.classList.contains('release-card') || entry.target.classList.contains('event-item') ? index * 100 : 0;
                setTimeout(() => {
                    entry.target.classList.add('is-visible');
                }, delay);
                observer.unobserve(entry.target);
            }
        });
    }, {
        rootMargin: '0px',
        threshold: 0.15
    });

    animatedElements.forEach(el => observer.observe(el));


    // --- 4. DISCOGRAPHY CAROUSEL LOGIC ---
    const carousel = document.querySelector('.releases-carousel');
    const prevButton = document.querySelector('.carousel-arrow.prev');
    const nextButton = document.querySelector('.carousel-arrow.next');

    if (carousel) {
        const scrollAmount = carousel.querySelector('.release-card').offsetWidth + 32; // card width + gap

        nextButton.addEventListener('click', () => {
            carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        prevButton.addEventListener('click', () => {
            carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });
    }
});