/* ============================================================
   FAMILIA MANAGER — LAYOUT.JS (carregado em todas as páginas)
   ============================================================ */

// ── Carregar dados do usuário na sidebar ─────────────────────
function loadLayoutUser() {
  const raw  = sessionStorage.getItem('user');
  const user = raw ? JSON.parse(raw) : null;

  if (!user) {
    // Sem sessão → redireciona para login
    // window.location.href = '../index.html';
    // (comentado para modo demo estático)
    return;
  }

  // Sidebar
  const nameEl  = document.getElementById('sb-user-name');
  const roleEl  = document.getElementById('sb-user-role');
  const famEl   = document.getElementById('sb-family-name');
  const avEl    = document.getElementById('sb-avatar');
  const topAvEl = document.getElementById('topbar-avatar');

  if (nameEl) nameEl.textContent = user.nome || 'Usuário';
  if (roleEl) roleEl.textContent = user.papel === 'admin' ? 'Administrador' : 'Membro';
  if (famEl)  famEl.textContent  = user.familia_nome || 'Minha Família';

  const initials = (user.nome || 'U').split(' ').map(n => n[0]).slice(0,2).join('').toUpperCase();
  if (avEl)    avEl.textContent  = initials;
  if (topAvEl) topAvEl.textContent = initials;

  // Notificações badge (demo: 3)
  const badge = document.getElementById('notif-count');
  const topBadge = document.getElementById('topbar-notif-badge');
  const count = parseInt(sessionStorage.getItem('notif_count') || '3');
  if (badge && count > 0) {
    badge.style.display = '';
    badge.textContent = count;
  }
  if (topBadge && count > 0) {
    topBadge.style.display = '';
    topBadge.textContent = count;
  }
}

// ── Logout ────────────────────────────────────────────────────
async function doLogout() {
  try {
    await fetch('../api/auth.php?action=logout');
  } catch(e) {}
  sessionStorage.clear();
  window.location.href = '../index.html';
}

// ── Active nav ────────────────────────────────────────────────
function highlightNav() {
  const page = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href') || '';
    item.classList.toggle('active', href === page);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  loadLayoutUser();
  highlightNav();
});
