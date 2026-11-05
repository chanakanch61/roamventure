/* Scroll stage for public pages: Lenis smooths wheel scrolling on GSAP's ticker and ScrollTrigger scrubs decorative scenes.
   Without the libraries, or with reduced motion, pages scroll natively and every element keeps its resting position. */
(() => {
  if (!document.body.classList.contains('public-dark') || !window.gsap || !window.ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);
  const media = gsap.matchMedia();

  media.add('(prefers-reduced-motion: no-preference)', () => {
    // Lenis only smooths the wheel; touch keeps native momentum. It runs on GSAP's ticker so triggers update in the same frame.
    let lenis = null;
    const tick = time => lenis.raf(time * 1000);
    if (window.Lenis) {
      lenis = new Lenis({ autoToggle: true, allowNestedScroll: true, stopInertiaOnNavigate: true, virtualScroll: () => !document.querySelector('dialog[open]') });
      window.rvLenis = lenis;
      lenis.on('scroll', ScrollTrigger.update);
      gsap.ticker.add(tick);
      gsap.ticker.lagSmoothing(0);
    }
    // Same-page links glide through Lenis, which honours scroll-padding and scroll-margin; the URL and focus still follow like native jumps.
    const glide = event => {
      const link = event.target.closest('a[href^="#"]');
      if (!lenis || !link || event.defaultPrevented || event.button || event.metaKey || event.ctrlKey || event.shiftKey || link.matches('.skip')) return;
      const target = link.hash.length > 1 && document.getElementById(decodeURIComponent(link.hash.slice(1)));
      if (!target) return;
      event.preventDefault();
      lenis.scrollTo(target, { onComplete: () => { if (target.matches('a,button,input,select,textarea,[tabindex]')) target.focus({ preventScroll: true }); } });
      history.pushState(null, '', link.hash);
    };
    document.addEventListener('click', glide);

    // Home hero: the photo sinks while the wordmark, story card and postcards lift away at different depths.
    const hero = document.querySelector('.home-slider');
    if (hero) {
      gsap.timeline({ scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } })
        .to(hero.querySelectorAll('.hero-picture'), { '--rv-depth': '16%', ease: 'none' }, 0)
        .to(hero.querySelectorAll('.adventure-heading'), { yPercent: -28, autoAlpha: .25, ease: 'none' }, 0)
        .to(hero.querySelectorAll('.adventure-story'), { y: -50, ease: 'none' }, 0)
        .to(hero.querySelectorAll('.adventure-postcards'), { y: -130, ease: 'none' }, 0);
    }

    // Departure board rows rise in sequence; rows already on screen at load stay put.
    const rows = gsap.utils.toArray('.rv-board-list>li').filter(row => row.getBoundingClientRect().top > innerHeight * 0.9);
    if (rows.length) {
      gsap.set(rows, { autoAlpha: 0, y: 28 });
      ScrollTrigger.batch(rows, { start: 'top 90%', once: true, onEnter: batch => gsap.to(batch, { autoAlpha: 1, y: 0, duration: .8, stagger: .08, ease: 'power3.out', overwrite: true }) });
    }

    // Why section: the photo and its note drift against each other.
    const why = document.querySelector('.why-image');
    if (why) {
      gsap.timeline({ scrollTrigger: { trigger: why, start: 'top bottom', end: 'bottom top', scrub: true } })
        .fromTo(why.querySelector(':scope>img'), { '--rv-depth': '-36px' }, { '--rv-depth': '36px', ease: 'none' }, 0)
        .fromTo(why.querySelector('.photo-note'), { '--rv-depth': '40px' }, { '--rv-depth': '-40px', ease: 'none' }, 0);
    }

    // Journey steps: a rule draws across the grid and each step lights as the rule reaches it.
    const steps = document.querySelector('.steps-grid');
    let stepLine = null;
    if (steps) {
      const items = [...steps.children];
      stepLine = document.createElement('span');
      stepLine.className = 'rv-steps-line';
      stepLine.setAttribute('aria-hidden', 'true');
      steps.append(stepLine);
      steps.classList.add('rv-steps-armed');
      ScrollTrigger.create({ trigger: steps, start: 'top 78%', end: 'bottom 60%', scrub: true, onUpdate: self => {
        stepLine.style.setProperty('--rv-progress', self.progress.toFixed(3));
        items.forEach((item, index) => item.classList.toggle('is-lit', self.progress >= index / items.length + .02));
      } });
    }

    // Footer wordmark: ROAM and VENTURE slide in from opposite edges and the arrow turns into place.
    // ROAM is a bare text node; it gets a <b> wrapper because every span in the wordmark is styled as outline text.
    const mark = document.querySelector('.footer-wordmark');
    if (mark && mark.firstChild?.nodeType === Node.TEXT_NODE && mark.firstChild.data.trim()) {
      const text = mark.firstChild, roam = document.createElement('b');
      roam.className = 'rv-mark-roam';
      text.replaceWith(roam);
      roam.append(text);
    }
    if (mark && mark.children.length > 1) {
      const parts = [...mark.children];
      gsap.timeline({ scrollTrigger: { trigger: mark, start: 'top bottom', end: 'bottom 70%', scrub: true } })
        .from(parts[0], { xPercent: -45, ease: 'none' }, 0)
        .from(parts[1], { xPercent: 35, ease: 'none' }, 0)
        .from(mark.querySelectorAll('.material-icons'), { rotate: -135, scale: .4, ease: 'none' }, 0);
    }

    document.fonts?.ready.then(() => ScrollTrigger.refresh());
    return () => {
      document.removeEventListener('click', glide);
      stepLine?.remove();
      steps?.classList.remove('rv-steps-armed');
      steps && [...steps.children].forEach(item => item.classList.remove('is-lit'));
      if (lenis) { gsap.ticker.remove(tick); gsap.ticker.lagSmoothing(500, 33); lenis.destroy(); window.rvLenis = null; }
    };
  });

  // Printing and a live switch to reduced motion both return every element to its resting state.
  addEventListener('beforeprint', () => media.revert());
})();
