/* global document, window */
(() => {
  'use strict';

  function initPopovers() {

  const popovers = [...document.querySelectorAll('.gws-po[data-gws-trigger]')];
  if (!popovers.length || typeof HTMLElement.prototype.showPopover !== 'function') return;

  const queue = [];
  const queued = new Set();
  const shown = new Set();
  let active = null;
  let pageLoaded = document.readyState === 'complete';

  function dismissed(el) {
    return document.cookie.split('; ').some((part) => part.split('=')[0] === `gws_po_dismissed_${el.dataset.gwsId}`);
  }

  function writeDismissed(el) {
    const days = Math.max(0, Number.parseInt(el.dataset.gwsDays || '7', 10) || 0);
    const name = `gws_po_dismissed_${el.dataset.gwsId}`;
    const maxAge = days > 0 ? `; Max-Age=${Math.min(days, 3650) * 86400}` : '';
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${name}=1; Path=/; SameSite=Lax${maxAge}${secure}`;
  }

  function next() {
    if (active) return;
    while (queue.length) {
      const el = queue.shift();
      queued.delete(el);
      if (dismissed(el) || shown.has(el)) continue;
      try {
        el.showPopover();
        shown.add(el);
        active = el;
        const first = el.querySelector('[data-gws-close]');
        if (first) first.focus({ preventScroll: true });
        return;
      } catch (err) {
        // Browser- oder DOM-Zustand nicht mehr kompatibel.
      }
    }
  }

  function enqueue(el) {
    if (dismissed(el) || shown.has(el) || queued.has(el)) return;
    queue.push(el);
    queued.add(el);
    next();
  }

  popovers.forEach((el) => {
    el.querySelectorAll('[data-gws-close]').forEach((button) => {
      button.addEventListener('click', () => {
        writeDismissed(el);
        if (el.matches(':popover-open')) el.hidePopover();
      });
    });

    // Escape und andere Browser-Schliessmechanismen gelten ebenfalls als Wegklicken.
    el.addEventListener('toggle', (event) => {
      if (event.newState !== 'closed') return;
      writeDismissed(el);
      if (active === el) active = null;
      next();
    });

    // popover=manual schliesst nicht automatisch bei Escape: explizit bereitstellen.
    el.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && el.matches(':popover-open')) {
        event.preventDefault();
        writeDismissed(el);
        el.hidePopover();
      }
    });
  });

  // Exit Intent: Maus verlässt das Fenster über den oberen Rand; Touch-Geräte werden ignoriert.
  const exitPopovers = popovers.filter((el) => el.dataset.gwsTrigger === 'exit');
  if (exitPopovers.length && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    document.addEventListener('mouseout', (event) => {
      if (event.relatedTarget !== null || event.clientY > 0) return;
      exitPopovers.forEach(enqueue);
    }, { passive: true });
  }

  function startTimers() {
    if (!pageLoaded) return;
    popovers.filter((el) => el.dataset.gwsTrigger === 'timed').forEach((el) => {
      const delay = Math.min(Math.max(Number.parseInt(el.dataset.gwsDelay || '5000', 10) || 0, 0), 2147483647);
      window.setTimeout(() => enqueue(el), delay);
    });
  }

  if (pageLoaded) startTimers();
  else window.addEventListener('load', () => { pageLoaded = true; startTimers(); }, { once: true });
  }

  // Footer-Scripts können vor dem bei wp_footer gerenderten Popover-HTML stehen.
  // Daher erst nach dem Parsen des vollständigen DOM initialisieren.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPopovers, { once: true });
  } else {
    initPopovers();
  }
})();
