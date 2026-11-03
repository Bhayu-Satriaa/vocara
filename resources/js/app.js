// Sidebar bisa mengecil; nilainya dibaca layout sebelum paint, di sini hanya tombol toggle.
document.querySelector('#toggle-sidebar')?.addEventListener('click', () => {
    const mengecil = document.documentElement.toggleAttribute('data-collapsed');
    localStorage.setItem('vocara.sidebar', mengecil ? 'collapsed' : 'full');
});

// Dropdown profil di header: klik avatar membuka/menutup menu logout.
const tombolProfil = document.querySelector('#tombol-profil');
const menuProfil = document.querySelector('#menu-profil');

if (tombolProfil && menuProfil) {
    const tutupProfil = () => {
        menuProfil.dataset.open = 'false';
        menuProfil.setAttribute('aria-hidden', 'true');
        tombolProfil.setAttribute('aria-expanded', 'false');
    };

    tombolProfil.addEventListener('click', (event) => {
        event.stopPropagation();
        const terbuka = menuProfil.dataset.open === 'true';
        menuProfil.dataset.open = terbuka ? 'false' : 'true';
        menuProfil.setAttribute('aria-hidden', terbuka ? 'true' : 'false');
        tombolProfil.setAttribute('aria-expanded', terbuka ? 'false' : 'true');
    });

    document.addEventListener('click', tutupProfil);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            tutupProfil();
        }
    });
}
