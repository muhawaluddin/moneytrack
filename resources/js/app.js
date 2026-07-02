import './bootstrap';

const preferred = localStorage.getItem('theme') || document.documentElement.dataset.theme || 'system';
if (preferred === 'dark' || (preferred === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) document.body.classList.add('dark');

document.querySelectorAll('[data-money]').forEach(input => {
    input.addEventListener('input', () => input.dataset.formatted = new Intl.NumberFormat('id-ID').format(input.value.replace(/\D/g, '') || 0));
});
