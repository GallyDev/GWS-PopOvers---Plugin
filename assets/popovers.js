/* GWS PopOvers: accessible, native modal dialogs (no jQuery). */
(() => {
  'use strict';

  function init() {
    const dialogs = [...document.querySelectorAll('dialog.gws-po[data-gws-trigger]')];
    if (!dialogs.length || typeof HTMLDialogElement === 'undefined' ||
        typeof HTMLDialogElement.prototype.showModal !== 'function') return;

    const queue = [];
    const queued = new Set();
    const shown = new Set();
    let active = null;
    let timersStarted = false;
    const previousFocus = new WeakMap();

    const cookieName = el => `gws_po_dismissed_${el.dataset.gwsId}`;
    function isDismissed(el) {
      return document.cookie.split(';').some(part => part.trim().split('=')[0] === cookieName(el));
    }
    function dismiss(el) {
      const days = Math.min(3650, Math.max(0, Number.parseInt(el.dataset.gwsDays || '7', 10) || 0));
      const maxAge = days ? `; Max-Age=${days * 86400}` : '';
      const secure = location.protocol === 'https:' ? '; Secure' : '';
      document.cookie = `${cookieName(el)}=1; Path=/; SameSite=Lax${maxAge}${secure}`;
    }
    function next() {
      if (active) return;
      while (queue.length) {
        const el = queue.shift();
        queued.delete(el);
        if (!el.isConnected || isDismissed(el) || shown.has(el)) continue;
        try {
          // Modal dialogs block the background; modeless dialogs keep it interactive.
          // Both are native HTMLDialogElement methods (not the Popover API).
          previousFocus.set(el, document.activeElement);
          if (el.dataset.gwsDisplay === 'nonmodal') el.show();
          else el.showModal();
          active = el;
          shown.add(el);
          return;
        } catch (error) {
          // Do not block other queued dialogs if an element cannot be opened.
        }
      }
    }
    function enqueue(el) {
      if (isDismissed(el) || shown.has(el) || queued.has(el)) return;
      // Exit intent takes priority over pending timers; never interrupt an open dialog.
      if (el.dataset.gwsTrigger === 'exit') {
        const firstTimed = queue.findIndex(item => item.dataset.gwsTrigger !== 'exit');
        if (firstTimed === -1) queue.push(el);
        else queue.splice(firstTimed, 0, el);
      } else queue.push(el);
      queued.add(el);
      next();
    }

    dialogs.forEach(el => {
      el.querySelectorAll('[data-gws-close]').forEach(button => {
        button.addEventListener('click', () => el.close('dismissed'));
      });
      // Native cancel allows Escape to close the dialog. Do not preventDefault().
      // All closure paths (Escape, button, outside click, script) persist dismissal.
      el.addEventListener('close', () => {
        dismiss(el);
        if (active === el) active = null;
        // Browsers restore focus for modal dialogs. For modeless auto-opened
        // dialogs explicitly restore focus only when safe and still connected.
        if (el.dataset.gwsDisplay === 'nonmodal') {
          const prior = previousFocus.get(el);
          if (prior && prior.isConnected && typeof prior.focus === 'function') {
            prior.focus({ preventScroll: true });
          }
        }
        // Allow the browser to finish dialog focus restoration before opening another.
        window.setTimeout(next, 0);
      });
      // Modal backdrop dismissal: compare actual pointer coordinates to dialog rect,
      // not event.target alone (a dialog backdrop can target the dialog itself).
      el.addEventListener('click', event => {
        if (el.dataset.gwsDisplay === 'nonmodal' || event.target !== el) return;
        const rect = el.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right ||
            event.clientY < rect.top || event.clientY > rect.bottom) {
          el.close('dismissed');
        }
      });
    });

    // A modeless dialog has no native backdrop. Dismiss on an outside click,
    // without swallowing that click or making the underlying page inert.
    document.addEventListener('pointerdown', event => {
      if (active && active.dataset.gwsDisplay === 'nonmodal' &&
          !active.contains(event.target)) active.close('dismissed');
    });

    // Desktop-only exit intent: pointer exits the viewport through its top edge.
    // Touch/keyboard users are served by timed campaigns instead.
    const exitDialogs = dialogs.filter(el => el.dataset.gwsTrigger === 'exit');
    if (exitDialogs.length && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
      document.addEventListener('mouseout', event => {
        if (event.relatedTarget !== null || event.clientY > 0) return;
        exitDialogs.forEach(enqueue);
      }, { passive: true });
    }

    function startTimers() {
      if (timersStarted) return;
      timersStarted = true;
      dialogs.filter(el => el.dataset.gwsTrigger === 'timed').forEach(el => {
        if (isDismissed(el)) return;
        const raw = Number.parseInt(el.dataset.gwsDelay || '5000', 10);
        const delay = Number.isFinite(raw) ? Math.min(2147483647, Math.max(0, raw)) : 5000;
        window.setTimeout(() => enqueue(el), delay);
      });
    }
    if (document.readyState === 'complete') startTimers();
    else window.addEventListener('load', startTimers, { once: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else init();
})();
