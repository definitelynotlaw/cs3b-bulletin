(() => {
  const root = document.documentElement;
  const storageKey = 'cs3b-bulletin-theme';

  const createThemeIcon = (isDark) => {
    const namespace = 'http://www.w3.org/2000/svg';
    const icon = document.createElementNS(namespace, 'svg');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '1.8');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('stroke-linejoin', 'round');
    icon.setAttribute('aria-hidden', 'true');
    icon.setAttribute('focusable', 'false');

    const shape = document.createElementNS(namespace, isDark ? 'path' : 'g');
    if (isDark) {
      shape.setAttribute('d', 'M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5a8.5 8.5 0 1 0 12 12Z');
    } else {
      const sun = document.createElementNS(namespace, 'circle');
      sun.setAttribute('cx', '12');
      sun.setAttribute('cy', '12');
      sun.setAttribute('r', '4');
      shape.append(sun);
      const rays = document.createElementNS(namespace, 'path');
      rays.setAttribute('d', 'M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42');
      shape.append(rays);
    }
    icon.append(shape);
    return icon;
  };

  const updateToggle = (theme, button) => {
    const isDark = theme === 'dark';
    button.setAttribute('aria-pressed', String(isDark));
    button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
    button.replaceChildren(createThemeIcon(isDark));
  };

  const updateBrandLogo = (theme) => {
    document.querySelectorAll('.site-brand__logo').forEach((img) => {
      img.src = theme === 'dark'
        ? 'assets/brand/pilimon-bulletin-wordmark-dark.svg'
        : 'assets/brand/pilimon-bulletin-wordmark-light.svg';
      img.alt = 'Pilimon Bulletin';
    });
  };

  let savedTheme;
  try {
    savedTheme = localStorage.getItem(storageKey);
  } catch {
    savedTheme = null;
  }

  if (savedTheme === 'light' || savedTheme === 'dark') {
    root.dataset.theme = savedTheme;
  }

  updateBrandLogo(root.dataset.theme === 'dark' ? 'dark' : 'light');

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'theme-toggle';
  updateToggle(root.dataset.theme, button);

  const nav = document.querySelector('.top nav');
  const main = document.querySelector('main.container');
  if (nav) {
    nav.append(button);
  } else if (main) {
    const row = document.createElement('div');
    row.className = 'theme-toggle-row';
    row.append(button);
    main.prepend(row);
  }

  const top = document.querySelector('.top');
  if (top && nav) {
    if (!nav.id) nav.id = 'primary-navigation';

    const menuButton = document.createElement('button');
    menuButton.type = 'button';
    menuButton.className = 'mobile-menu-toggle';
    menuButton.setAttribute('aria-controls', nav.id);

    const updateMenu = (isOpen) => {
      nav.classList.toggle('is-open', isOpen);
      menuButton.setAttribute('aria-expanded', String(isOpen));
      menuButton.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');

      const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      icon.setAttribute('viewBox', '0 0 24 24');
      icon.setAttribute('fill', 'none');
      icon.setAttribute('stroke', 'currentColor');
      icon.setAttribute('stroke-width', '2');
      icon.setAttribute('stroke-linecap', 'round');
      icon.setAttribute('aria-hidden', 'true');
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', isOpen ? 'm6 6 12 12M18 6 6 18' : 'M4 7h16M4 12h16M4 17h16');
      icon.append(path);
      menuButton.replaceChildren(icon);
    };

    top.insertBefore(menuButton, nav);
    root.classList.add('has-mobile-menu');
    updateMenu(false);

    menuButton.addEventListener('click', () => {
      updateMenu(menuButton.getAttribute('aria-expanded') !== 'true');
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => updateMenu(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
        updateMenu(false);
        menuButton.focus();
      }
    });

    window.matchMedia('(min-width: 881px)').addEventListener('change', (event) => {
      if (event.matches) updateMenu(false);
    });
  }

  button.addEventListener('click', () => {
    const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = theme;
    updateToggle(theme, button);
    updateBrandLogo(theme);
    try {
      localStorage.setItem(storageKey, theme);
    } catch {
      return;
    }
  });

  const filterTriggers = document.querySelectorAll('.filter-trigger[aria-controls]');
  let openFilter = null;

  const closeFilter = (returnFocus = false) => {
    if (!openFilter) return;
    const { trigger, panel } = openFilter;
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    openFilter = null;
    if (returnFocus) trigger.focus();
  };

  const positionFilter = () => {
    if (!openFilter) return;
    const { trigger, panel } = openFilter;
    const triggerRect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const margin = 12;
    const left = Math.max(margin, Math.min(
      triggerRect.left + triggerRect.width / 2 - panelRect.width / 2,
      document.documentElement.clientWidth - panelRect.width - margin
    ));
    const below = triggerRect.bottom + 8;
    const above = triggerRect.top - panelRect.height - 8;
    const maxTop = Math.max(margin, window.innerHeight - panelRect.height - margin);
    const top = Math.min(Math.max(below + panelRect.height <= window.innerHeight - margin ? below : above, margin), maxTop);
    panel.style.left = `${left}px`;
    panel.style.top = `${top}px`;
  };

  filterTriggers.forEach((trigger) => {
    const panel = document.getElementById(trigger.getAttribute('aria-controls'));
    if (!panel) return;
    trigger.addEventListener('click', () => {
      if (openFilter?.trigger === trigger) {
        closeFilter();
        return;
      }
      closeFilter();
      panel.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      openFilter = { trigger, panel };
      positionFilter();
      panel.querySelector('input, select, button')?.focus();
    });
  });

  document.addEventListener('click', (event) => {
    if (openFilter && !openFilter.panel.contains(event.target) && !openFilter.trigger.contains(event.target)) {
      closeFilter();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && openFilter) closeFilter(true);
  });

  window.addEventListener('resize', positionFilter);
  window.addEventListener('scroll', positionFilter, true);

  const lightbox = document.querySelector('.lightbox');
  document.querySelectorAll('[data-lightbox]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      if (!lightbox) return;
      const image = lightbox.querySelector('img');
      if (!image) return;
      image.src = trigger.dataset.lightbox;
      image.alt = trigger.getAttribute('aria-label') || 'Expanded announcement image';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
    });
  });

  if (lightbox) {
    const closeButton = lightbox.querySelector('.lightbox-close');
    const close = () => {
      lightbox.hidden = true;
      document.body.style.overflow = '';
    };
    closeButton?.addEventListener('click', close);
    lightbox.addEventListener('click', (event) => {
      if (event.target === lightbox) close();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !lightbox.hidden) close();
    });
  }
})();