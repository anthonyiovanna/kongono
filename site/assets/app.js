(() => {
  // 1. Dark / Light theme toggle persisted in localStorage
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('kongono-theme');
  if (savedTheme === 'light' || savedTheme === 'dark') {
    root.setAttribute('data-theme', savedTheme);
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    const syncLabel = () => {
      const isLight = root.getAttribute('data-theme') === 'light';
      btn.textContent = isLight ? 'Dark Mode' : 'Light Mode';
    };
    syncLabel();
    btn.addEventListener('click', () => {
      const next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
      root.setAttribute('data-theme', next);
      localStorage.setItem('kongono-theme', next);
      syncLabel();
    });
  });

  // 2. Interactive code showcase tabs
  document.querySelectorAll('[data-tabs]').forEach((wrap) => {
    const buttons = wrap.querySelectorAll('[data-tab-target]');
    const panels = wrap.querySelectorAll('[data-tab-panel]');
    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = btn.getAttribute('data-tab-target');
        buttons.forEach((b) => b.classList.toggle('active', b === btn));
        panels.forEach((p) => p.classList.toggle('active', p.getAttribute('data-tab-panel') === target));
      });
    });
  });

  // 3. Copy-to-clipboard buttons (both explicit [data-copy] and auto-added on docs <pre> blocks)
  const attachCopy = (btn, getText) => {
    btn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(getText());
        const prev = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => { btn.textContent = prev; }, 1600);
      } catch {
        btn.textContent = 'Select & Copy';
      }
    });
  };

  document.querySelectorAll('[data-copy]').forEach((btn) => {
    attachCopy(btn, () => btn.getAttribute('data-copy') || '');
  });

  document.querySelectorAll('.docs-content pre').forEach((pre) => {
    const wrap = document.createElement('div');
    wrap.className = 'code-block';
    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(pre);
    const btn = document.createElement('button');
    btn.className = 'copy-btn';
    btn.type = 'button';
    btn.textContent = 'Copy';
    wrap.appendChild(btn);
    attachCopy(btn, () => pre.textContent || '');
  });

  // 4. Instant client-side search filter on docs.html (`/` or Ctrl+K shortcut)
  const searchInput = document.getElementById('docSearch');
  if (searchInput) {
    const sections = Array.from(document.querySelectorAll('.docs-content section'));
    const navItems = Array.from(document.querySelectorAll('.sidebar li'));

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim().toLowerCase();
      sections.forEach((sec) => {
        const match = !q || sec.textContent.toLowerCase().includes(q);
        sec.hidden = !match;
      });
      navItems.forEach((li) => {
        const link = li.querySelector('a');
        const id = link ? (link.getAttribute('href') || '').replace('#', '') : '';
        const sec = id ? document.getElementById(id) : null;
        const visible = !q || (sec && !sec.hidden) || li.textContent.toLowerCase().includes(q);
        li.hidden = !visible;
      });
    });

    document.addEventListener('keydown', (e) => {
      if ((e.key === '/' && document.activeElement !== searchInput) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
        e.preventDefault();
        searchInput.focus();
      }
    });
  }
})();
