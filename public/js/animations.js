// Semua logika GSAP & ScrollTrigger
document.addEventListener("DOMContentLoaded", () => {
    // Registrasi plugin
    gsap.registerPlugin(ScrollTrigger);

    // 1. Hero Animations (Teks muncul dari bawah)
    const tlHero = gsap.timeline();
    tlHero.from(".hero-text", {
        y: 50,
        opacity: 0,
        duration: 1,
        stagger: 0.2,
        ease: "power3.out",
        delay: 0.2
    });

    // 2. Glow Logo Berdenyut (Infinite Pulse)
    gsap.to(".logo-glow", {
        boxShadow: "0 0 25px 10px rgba(110, 250, 128, 0.4), 0 0 50px 20px rgba(27, 94, 32, 0.2)",
        scale: 1.05,
        duration: 2,
        repeat: -1,
        yoyo: true,
        ease: "sine.inOut"
    });

    // 3. Fade-in Section saat scroll
    gsap.utils.toArray('.gsap-fade-up').forEach(section => {
        gsap.from(section, {
            scrollTrigger: {
                trigger: section,
                start: "top 85%", // Mulai animasi saat elemen 85% dari atas viewport
                toggleActions: "play none none reverse"
            },
            y: 60,
            opacity: 0,
            duration: 0.8,
            ease: "power2.out"
        });
    });

    // 4. Navbar Sticky transisi background (Tanpa gerakan naik)
    // 4. Navbar Sticky transisi background (Tanpa gerakan naik)
    window.addEventListener('scroll', () => {
        const navInner = document.querySelector('#navbar > div > div');
        if (window.scrollY > 50) {
            navInner.classList.add('', '');
            navInner.classList.remove('', '', '');
        } else {
            navInner.classList.remove('', '');
            navInner.classList.add('', '', '');
        }
    });
});
