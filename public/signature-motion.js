/* Signature motion for public pages. Decorative layers are aria-hidden; content stays visible without JavaScript. */
(() => {
  if (!document.body.classList.contains('public-dark')) return;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const observes = 'IntersectionObserver' in window;
  const below = element => element.getBoundingClientRect().top > innerHeight;
  const positioned = element => { if (getComputedStyle(element).position === 'static') element.style.position = 'relative'; };

  // Image hosts share one layer: ::before is the curtain slab, ::after is the hover sheen.
  const hosts = [...document.querySelectorAll('.tour-card .card-image,.destination-card,.why-image,.home-journal-photo,.blog-card-image,.rv-story-image,.rv-style-card,.trip-photo')];
  hosts.forEach(host => {
    positioned(host);
    const layer = document.createElement('span');
    layer.className = 'rv-fx';
    layer.setAttribute('aria-hidden', 'true');
    host.append(layer);
    host.classList.add('rv-fx-host');
  });
  const play = host => {
    if (!host.classList.contains('rv-curtain-pending')) return;
    const delay = getComputedStyle(host.closest('.rv-reveal') || host).getPropertyValue('--reveal-delay').trim();
    host.style.setProperty('--curtain-delay', delay || '0ms');
    host.classList.replace('rv-curtain-pending', 'rv-curtain-play');
  };
  const settle = host => host.classList.remove('rv-curtain-pending', 'rv-curtain-play');
  hosts.forEach(host => host.addEventListener('animationend', event => { if (event.animationName === 'rv-curtain-zoom') settle(host); }));
  if (observes && !reduced.matches) {
    const curtains = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { curtains.unobserve(entry.target); play(entry.target); }
    }), { threshold: 0.2 });
    hosts.forEach(host => { if (below(host)) { host.classList.add('rv-curtain-pending'); curtains.observe(host); } });
    document.addEventListener('focusin', event => { const host = event.target.closest('.rv-curtain-pending'); if (host) settle(host); });
  }

  // Thai marks must stay with their base letter, so headings split by grapheme and never fall back to code points.
  const headings = observes && 'Segmenter' in Intl && !reduced.matches
    ? [...document.querySelectorAll('main h2')].filter(heading => !heading.closest('.blog-card,.journal-cover,.home-journal-card,.blog-prose,.onpage-magazine,.booking-panel,form,dialog,[popover]'))
    : [];
  const graphemes = headings.length ? new Intl.Segmenter('th', { granularity: 'grapheme' }) : null;
  headings.forEach(heading => {
    const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT, { acceptNode: node => node.parentElement.closest('.material-icons') || !node.data.trim() ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT });
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    let count = 0;
    nodes.forEach(node => {
      const label = document.createElement('span');
      label.className = 'rv-split-sr';
      label.textContent = node.data;
      const visual = document.createElement('span');
      visual.setAttribute('aria-hidden', 'true');
      for (const { segment } of graphemes.segment(node.data)) {
        if (!segment.trim()) { visual.append(segment); continue; }
        const char = document.createElement('span');
        char.className = 'rv-lit-char';
        char.style.setProperty('--i', count++);
        char.textContent = segment;
        visual.append(char);
      }
      node.replaceWith(label, visual);
    });
    heading.style.setProperty('--n', count);
  });
  const light = heading => {
    const progress = (innerHeight * 0.92 - heading.getBoundingClientRect().top) / (innerHeight * 0.34);
    heading.style.setProperty('--lit', Math.min(1, Math.max(0, progress)).toFixed(3));
  };
  const lit = new Set();
  let queued = false;
  const paint = () => { queued = false; lit.forEach(light); };
  if (headings.length) {
    headings.forEach(light);
    const watch = new IntersectionObserver(entries => entries.forEach(entry => {
      light(entry.target);
      entry.isIntersecting ? lit.add(entry.target) : lit.delete(entry.target);
    }));
    headings.forEach(heading => watch.observe(heading));
    addEventListener('scroll', () => { if (!queued) { queued = true; requestAnimationFrame(paint); } }, { passive: true });
    addEventListener('resize', paint);
  }

  // A hairline draws under each section heading and retracts when the reader scrolls back above it.
  if (observes && !reduced.matches) {
    const rules = new IntersectionObserver(entries => entries.forEach(entry => {
      const rule = entry.target.querySelector(':scope>.rv-rule');
      if (entry.isIntersecting) { rule.style.transformOrigin = 'left'; rule.classList.add('is-drawn'); }
      else if (entry.boundingClientRect.top > 0) { rule.style.transformOrigin = 'right'; rule.classList.remove('is-drawn'); }
    }), { rootMargin: '0px 0px -12% 0px' });
    document.querySelectorAll('.section-head,.home-journal-heading,.rv-section-heading,.trip-section-heading').forEach(head => {
      positioned(head);
      const rule = document.createElement('span');
      rule.className = 'rv-rule';
      rule.setAttribute('aria-hidden', 'true');
      head.append(rule);
      rules.observe(head);
    });
  }

  // Departure board: each character becomes a split-flap tile that rolls before settling on its real value.
  const flaps = [...document.querySelectorAll('.rv-board [data-flap]')].filter(element => /^[\x21-\x7e]{1,8}$/.test(element.textContent.trim()));
  flaps.forEach(element => {
    const text = element.textContent.trim();
    element.textContent = '';
    [...text].forEach(character => {
      const tile = document.createElement('span');
      tile.className = 'rv-flap-tile';
      tile.textContent = character;
      element.append(tile);
    });
    element.classList.add('is-tiled');
  });
  const roll = (element, delay) => element.querySelectorAll('.rv-flap-tile').forEach((tile, index) => {
    const final = tile.textContent, pool = /\d/.test(final) ? '0123456789' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const steps = 5 + index * 2;
    let step = 0;
    const flip = () => {
      tile.textContent = step < steps ? pool[Math.floor(Math.random() * pool.length)] : final;
      if (step++ < steps) setTimeout(flip, 55);
    };
    setTimeout(flip, delay);
  });
  if (flaps.length && observes && !reduced.matches) {
    const rows = new IntersectionObserver(entries => entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      rows.unobserve(entry.target);
      const order = [...entry.target.parentElement.children].indexOf(entry.target);
      entry.target.querySelectorAll('[data-flap].is-tiled').forEach(element => roll(element, order * 110));
    }), { threshold: 0.4 });
    document.querySelectorAll('.rv-board-list>li').forEach(row => rows.observe(row));
  }

  const showAll = () => {
    hosts.forEach(settle);
    headings.forEach(heading => heading.style.setProperty('--lit', 1));
  };
  document.addEventListener('rv:motion', event => { if (event.detail.paused) showAll(); });
  addEventListener('beforeprint', showAll);
})();
