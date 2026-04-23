/* ============================================================
   FAMILIA MANAGER — APP.JS
   ============================================================ */

// ── Toast Notifications ──────────────────────────────────────
function showToast(msg, type = 'info', duration = 3500) {
  const icons = { success: '✅', warning: '⚠️', danger: '❌', info: '💬' };
  const container = document.getElementById('toast-container') ||
    (() => {
      const el = document.createElement('div');
      el.id = 'toast-container';
      el.className = 'toast-container';
      document.body.appendChild(el);
      return el;
    })();

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${icons[type] || '💬'}</span>
                     <span class="toast-msg">${msg}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = '.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ── Modal ─────────────────────────────────────────────────────
function openModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) overlay.classList.add('open');
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) overlay.classList.remove('open');
}

// Fechar modal ao clicar fora
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ── Sidebar active link ───────────────────────────────────────
function setActiveNav() {
  const page = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href') || '';
    if (href === page || (page === 'index.html' && href === 'dashboard.html')) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });
}

// ── Mini Calendar ─────────────────────────────────────────────
class MiniCalendar {
  constructor(containerId, events = []) {
    this.container = document.getElementById(containerId);
    this.events = events; // array de { date: 'YYYY-MM-DD', ... }
    this.current = new Date();
    if (this.container) this.render();
  }

  render() {
    const year = this.current.getFullYear();
    const month = this.current.getMonth();
    const today = new Date();
    const monthNames = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                        'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    const dayNames = ['D','S','T','Q','Q','S','S'];

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrev  = new Date(year, month, 0).getDate();

    let html = `<div class="mini-calendar">
      <div class="mini-cal-header">
        <button onclick="window._cal_${this.container.id}.prev()">‹</button>
        <span class="month-name">${monthNames[month]} ${year}</span>
        <button onclick="window._cal_${this.container.id}.next()">›</button>
      </div>
      <div class="mini-cal-grid">`;

    dayNames.forEach(d => { html += `<div class="day-name">${d}</div>`; });

    for (let i = 0; i < firstDay; i++) {
      const d = daysInPrev - firstDay + i + 1;
      html += `<div class="day other-month">${d}</div>`;
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      const hasEv   = this.events.some(e => e.date === dateStr);
      html += `<div class="day${isToday?' today':''}${hasEv?' has-event':''}">${d}</div>`;
    }

    const remaining = 42 - firstDay - daysInMonth;
    for (let d = 1; d <= remaining; d++) {
      html += `<div class="day other-month">${d}</div>`;
    }

    html += `</div></div>`;
    this.container.innerHTML = html;
    window[`_cal_${this.container.id}`] = this;
  }

  prev() { this.current.setMonth(this.current.getMonth() - 1); this.render(); }
  next() { this.current.setMonth(this.current.getMonth() + 1); this.render(); }
}

// ── Charts (usando Canvas API nativo) ────────────────────────
function drawBarChart(canvasId, labels, values, colors) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.offsetWidth;
  const H = canvas.height = canvas.offsetHeight;
  const pad = { top: 20, right: 20, bottom: 40, left: 50 };
  const chartW = W - pad.left - pad.right;
  const chartH = H - pad.top  - pad.bottom;
  const max = Math.max(...values) * 1.2 || 1;
  const barW = (chartW / labels.length) * 0.55;
  const gap  = (chartW / labels.length) * 0.45;

  ctx.clearRect(0, 0, W, H);

  // Grid lines
  ctx.strokeStyle = '#E4E8F0';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (chartH / 4) * i;
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
    ctx.fillStyle = '#8A94A6';
    ctx.font = '11px Inter, sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText(Math.round(max - (max / 4) * i), pad.left - 6, y + 4);
  }

  // Bars
  values.forEach((v, i) => {
    const x = pad.left + i * (barW + gap) + gap / 2;
    const barH = (v / max) * chartH;
    const y = pad.top + chartH - barH;

    // Gradient
    const grad = ctx.createLinearGradient(0, y, 0, y + barH);
    grad.addColorStop(0, colors[i % colors.length]);
    grad.addColorStop(1, colors[i % colors.length] + '88');
    ctx.fillStyle = grad;

    const r = 6;
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + barW - r, y);
    ctx.quadraticCurveTo(x + barW, y, x + barW, y + r);
    ctx.lineTo(x + barW, y + barH);
    ctx.lineTo(x, y + barH);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
    ctx.fill();

    // Label
    ctx.fillStyle = '#8A94A6';
    ctx.font = '11px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(labels[i], x + barW / 2, H - pad.bottom + 16);
  });
}

function drawDonutChart(canvasId, values, colors, labels) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.offsetWidth;
  const H = canvas.height = canvas.offsetHeight;
  const cx = W / 2, cy = H / 2;
  const r = Math.min(W, H) / 2 - 20;
  const inner = r * 0.58;
  const total = values.reduce((a, b) => a + b, 0) || 1;

  ctx.clearRect(0, 0, W, H);

  let startAngle = -Math.PI / 2;
  values.forEach((v, i) => {
    const slice = (v / total) * 2 * Math.PI;
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, r, startAngle, startAngle + slice);
    ctx.closePath();
    ctx.fillStyle = colors[i % colors.length];
    ctx.fill();
    startAngle += slice;
  });

  // Inner circle (donut hole)
  ctx.beginPath();
  ctx.arc(cx, cy, inner, 0, 2 * Math.PI);
  ctx.fillStyle = '#fff';
  ctx.fill();

  // Center text
  ctx.fillStyle = '#2D3250';
  ctx.font = 'bold 18px Inter, sans-serif';
  ctx.textAlign = 'center';
  ctx.fillText('Total', cx, cy - 4);
  ctx.font = '12px Inter, sans-serif';
  ctx.fillStyle = '#8A94A6';
  ctx.fillText(total.toLocaleString('pt-BR'), cx, cy + 16);
}

function drawLineChart(canvasId, labels, datasets) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width  = canvas.offsetWidth;
  const H = canvas.height = canvas.offsetHeight;
  const pad = { top: 20, right: 20, bottom: 40, left: 60 };
  const chartW = W - pad.left - pad.right;
  const chartH = H - pad.top  - pad.bottom;
  const allVals = datasets.flatMap(d => d.values);
  const max = Math.max(...allVals) * 1.15 || 1;

  ctx.clearRect(0, 0, W, H);

  // Grid
  ctx.strokeStyle = '#E4E8F0';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + (chartH / 4) * i;
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(W - pad.right, y); ctx.stroke();
    ctx.fillStyle = '#8A94A6';
    ctx.font = '11px Inter, sans-serif';
    ctx.textAlign = 'right';
    const val = Math.round(max - (max / 4) * i);
    ctx.fillText(val >= 1000 ? (val/1000).toFixed(1)+'k' : val, pad.left - 6, y + 4);
  }

  // X labels
  labels.forEach((lbl, i) => {
    const x = pad.left + (i / (labels.length - 1)) * chartW;
    ctx.fillStyle = '#8A94A6';
    ctx.font = '11px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(lbl, x, H - pad.bottom + 16);
  });

  // Lines
  datasets.forEach(ds => {
    const pts = ds.values.map((v, i) => ({
      x: pad.left + (i / (ds.values.length - 1)) * chartW,
      y: pad.top + chartH - (v / max) * chartH
    }));

    // Area fill
    ctx.beginPath();
    ctx.moveTo(pts[0].x, pad.top + chartH);
    pts.forEach(p => ctx.lineTo(p.x, p.y));
    ctx.lineTo(pts[pts.length-1].x, pad.top + chartH);
    ctx.closePath();
    ctx.fillStyle = ds.color + '22';
    ctx.fill();

    // Line
    ctx.beginPath();
    pts.forEach((p, i) => i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y));
    ctx.strokeStyle = ds.color;
    ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round';
    ctx.stroke();

    // Dots
    pts.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, 4, 0, 2 * Math.PI);
      ctx.fillStyle = '#fff';
      ctx.fill();
      ctx.strokeStyle = ds.color;
      ctx.lineWidth = 2;
      ctx.stroke();
    });
  });
}

// ── Checkbox toggle ───────────────────────────────────────────
function initCheckItems() {
  document.querySelectorAll('.check-item').forEach(item => {
    item.addEventListener('click', () => {
      const cb = item.querySelector('input[type="checkbox"]');
      if (cb) cb.checked = !cb.checked;
    });
  });
}

// ── Confirm delete ────────────────────────────────────────────
function confirmDelete(msg, callback) {
  if (confirm(msg || 'Tem certeza que deseja excluir?')) callback();
}

// ── Format currency ───────────────────────────────────────────
function formatCurrency(val) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
}

// ── Format date ───────────────────────────────────────────────
function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('pt-BR');
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  setActiveNav();
  initCheckItems();
});
