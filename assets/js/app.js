const navbar = document.querySelector('.dn-navbar');

document.querySelectorAll('[data-donation-amount]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById('donationAmount');
    if (input) input.value = button.dataset.donationAmount;
  });
});

function updateNavbar() {
  if (!navbar) return;
  navbar.classList.toggle('scrolled', window.scrollY > 20);
}

window.addEventListener('scroll', updateNavbar);
updateNavbar();

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const basePath = document.querySelector('link[rel="manifest"]')?.getAttribute('href')?.replace('manifest.json', '') || './';
    navigator.serviceWorker.register(`${basePath}service-worker.js`).catch(() => {});
  });
}

const progressBar = document.querySelector('.reading-progress span');

function updateProgress() {
  if (!progressBar) return;
  const total = document.documentElement.scrollHeight - window.innerHeight;
  const progress = total > 0 ? (window.scrollY / total) * 100 : 0;
  progressBar.style.width = `${Math.min(100, progress)}%`;
}

window.addEventListener('scroll', updateProgress);
updateProgress();

document.querySelectorAll('[data-reader-theme]').forEach((button) => {
  button.addEventListener('click', () => {
    document.body.classList.remove('reader-light', 'reader-paper');
    if (button.dataset.readerTheme !== 'dark') {
      document.body.classList.add(`reader-${button.dataset.readerTheme}`);
    }
    localStorage.setItem('darknovels-theme', button.dataset.readerTheme);
  });
});

const savedTheme = localStorage.getItem('darknovels-theme');
if (savedTheme && savedTheme !== 'dark') {
  document.body.classList.add(`reader-${savedTheme}`);
}

let fontSize = Number(localStorage.getItem('darknovels-font') || 1.16);

function applyFontSize() {
  document.documentElement.style.setProperty('--reader-font', `${fontSize}rem`);
  localStorage.setItem('darknovels-font', String(fontSize));
}

document.querySelectorAll('[data-font-step]').forEach((button) => {
  button.addEventListener('click', () => {
    fontSize += Number(button.dataset.fontStep) * 0.08;
    fontSize = Math.max(0.96, Math.min(1.72, fontSize));
    applyFontSize();
  });
});

applyFontSize();
