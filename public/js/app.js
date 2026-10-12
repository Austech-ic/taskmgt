/* Devhail front-end helpers. Plain JS + Alpine.js, no build step. */
(function () {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  // ----- Theme (light / dark / system) -----
  window.devhailTheme = {
    get() { try { return localStorage.getItem('dh-theme') || 'system'; } catch (e) { return 'system'; } },
    set(mode) {
      try { localStorage.setItem('dh-theme', mode); } catch (e) {}
      this.apply(mode);
    },
    apply(mode) {
      const root = document.documentElement;
      if (mode === 'system') root.removeAttribute('data-theme'); else root.setAttribute('data-theme', mode);
    },
    toggle() {
      const dark = document.documentElement.getAttribute('data-theme') === 'dark' ||
        (!document.documentElement.getAttribute('data-theme') && matchMedia('(prefers-color-scheme: dark)').matches);
      this.set(dark ? 'light' : 'dark');
      renderIcons();
    }
  };

  // ----- Icons -----
  // Only plain <i data-lucide> placeholders are converted, so re-running is cheap and idempotent.
  function renderIcons() {
    if (!window.lucide) return;
    const pending = document.querySelectorAll('i[data-lucide]');
    if (!pending.length) return;
    pending.forEach((el) => { el.setAttribute('data-icon', el.getAttribute('data-lucide')); el.removeAttribute('data-lucide'); });
    window.lucide.createIcons({ nameAttr: 'data-icon', attrs: { 'stroke-width': 2 } });
  }
  window.renderIcons = renderIcons;
  // Convert icons before Alpine boots, then watch for icons added by Alpine templates.
  renderIcons();
  let iconTimer;
  new MutationObserver(() => { clearTimeout(iconTimer); iconTimer = setTimeout(renderIcons, 20); })
    .observe(document.documentElement, { childList: true, subtree: true });

  async function postJson(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify(body || {}),
      credentials: 'same-origin'
    });
    let data = {};
    try { data = await res.json(); } catch (e) {}
    if (!res.ok) throw new Error(data.message || (data.errors && Object.values(data.errors)[0][0]) || 'Something went wrong. Try again.');
    return data;
  }
  window.postJson = postJson;

  document.addEventListener('alpine:init', () => {
    /**
     * Generic AI helper. Usage:
     * x-data="aiAssist('profile_bio', () => ({...inputs}))" then call run() and use result.
     */
    Alpine.data('aiAssist', (feature, collect, apply) => ({
      loading: false, error: '', result: null,
      async run() {
        this.loading = true; this.error = '';
        try {
          const data = await postJson(window.DEVHAIL.aiUrl.replace('__FEATURE__', feature), collect ? collect.call(this) : {});
          this.result = data.result;
          if (apply) apply.call(this, data.result);
        } catch (e) { this.error = e.message; }
        finally { this.loading = false; this.$nextTick(renderIcons); }
      }
    }));

    // Help assistant ("Hail")
    Alpine.data('assistant', () => ({
      open: false, input: '', busy: false,
      messages: [{ role: 'assistant', content: "Hey 👋 I'm Hail. Ask me anything about verification, hiring, payments or fees." }],
      toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => { this.$refs.input?.focus(); renderIcons(); }); },
      async send() {
        const q = this.input.trim();
        if (!q || this.busy) return;
        this.messages.push({ role: 'user', content: q });
        this.input = ''; this.busy = true; this.scroll();
        try {
          const data = await postJson(window.DEVHAIL.assistantUrl, { question: q, history: this.messages.slice(-7, -1) });
          this.messages.push({ role: 'assistant', content: data.answer });
        } catch (e) {
          this.messages.push({ role: 'assistant', content: e.message });
        } finally { this.busy = false; this.scroll(); }
      },
      scroll() { this.$nextTick(() => { const m = this.$refs.msgs; if (m) m.scrollTop = m.scrollHeight; }); }
    }));

    // Timed assessment
    Alpine.data('exam', (cfg) => ({
      current: 0,
      total: cfg.total,
      answers: cfg.answers || {},
      seconds: cfg.seconds,
      focusLosses: cfg.focusLosses || 0,
      saving: false, savedAt: null, submitting: false,
      init() {
        this.tick = setInterval(() => {
          this.seconds = Math.max(0, this.seconds - 1);
          if (this.seconds === 0) { clearInterval(this.tick); this.submit(true); }
        }, 1000);
        this.autosave = setInterval(() => this.save(), 20000);
        document.addEventListener('visibilitychange', () => { if (document.hidden) this.focusLosses++; });
        window.addEventListener('beforeunload', (e) => { if (!this.submitting) { e.preventDefault(); e.returnValue = ''; } });
      },
      get clock() {
        const m = Math.floor(this.seconds / 60), s = this.seconds % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
      },
      get answeredCount() { return Object.values(this.answers).filter(v => Array.isArray(v) ? v.length : v !== '' && v != null).length; },
      isAnswered(id) { const v = this.answers[id]; return Array.isArray(v) ? v.length > 0 : v !== undefined && v !== ''; },
      go(i) { this.current = Math.max(0, Math.min(this.total, i)); window.scrollTo({ top: 0, behavior: 'smooth' }); },
      async save() {
        this.saving = true;
        try { await postJson(cfg.saveUrl, { answers: this.answers, focus_losses: this.focusLosses }); this.savedAt = new Date(); }
        catch (e) {} finally { this.saving = false; }
      },
      submit(auto) {
        if (!auto && !confirm(`Submit now? You've answered ${this.answeredCount} of ${this.total}.`)) return;
        this.submitting = true;
        clearInterval(this.tick); clearInterval(this.autosave);
        this.$refs.answers.value = JSON.stringify(this.answers);
        this.$refs.focus.value = this.focusLosses;
        this.$refs.form.submit();
      }
    }));

    // Repeating milestone rows
    Alpine.data('milestones', (initial, total) => ({
      rows: initial && initial.length ? initial : [{ title: '', amount: '', due_on: '', description: '' }],
      add() { this.rows.push({ title: '', amount: '', due_on: '', description: '' }); this.$nextTick(renderIcons); },
      remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
      get sum() { return this.rows.reduce((t, r) => t + (parseFloat(String(r.amount).replace(/,/g, '')) || 0), 0); },
      fmt(n) { return new Intl.NumberFormat().format(Math.round(n * 100) / 100); }
    }));
  });

  document.addEventListener('DOMContentLoaded', () => {
    renderIcons();

    // Sticky nav border
    const nav = document.querySelector('.site-nav');
    if (nav) {
      const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 8);
      onScroll(); window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Confirm before destructive submits: <form data-confirm="Sure?">
    document.addEventListener('submit', (e) => {
      const msg = e.target.getAttribute('data-confirm');
      if (msg && !confirm(msg)) { e.preventDefault(); return; }
      const btn = e.target.querySelector('button[type="submit"]:not([data-no-loading])');
      if (btn && !e.defaultPrevented) setTimeout(() => btn.classList.add('is-loading'), 0);
    });

    // Auto-dismiss toasts
    document.querySelectorAll('.toasts .alert').forEach((el) => setTimeout(() => {
      el.style.transition = 'opacity .4s, transform .4s'; el.style.opacity = '0'; el.style.transform = 'translateY(8px)';
      setTimeout(() => el.remove(), 450);
    }, 6000));

    // Copy buttons: <button data-copy="text">
    document.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', () => {
      navigator.clipboard?.writeText(b.getAttribute('data-copy'));
      const old = b.innerHTML; b.textContent = 'Copied ✓'; setTimeout(() => { b.innerHTML = old; renderIcons(); }, 1500);
    }));
  });
})();
