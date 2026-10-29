// Sidebar bisa mengecil; nilainya dibaca layout sebelum paint, di sini hanya tombol toggle.
document.querySelector('#toggle-sidebar')?.addEventListener('click', () => {
    const mengecil = document.documentElement.toggleAttribute('data-collapsed');
    localStorage.setItem('vocara.sidebar', mengecil ? 'collapsed' : 'full');
});

