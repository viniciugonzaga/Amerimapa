import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
// resources/js/app.js
document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('global-loader');
    document.querySelectorAll('a[data-link]').forEach(link => {
        link.addEventListener('click', () => {
            loader.classList.remove('hidden');
        });
    });
});
