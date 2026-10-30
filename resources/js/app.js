import Alpine from 'alpinejs';

/*
 * Status sidebar untuk layout utama (bagian 7.4 spesifikasi).
 * Di layar ≥ 1024px tombol hamburger menyembunyikan/menampilkan sidebar;
 * di layar lebih kecil sidebar muncul sebagai drawer dari kiri.
 */
Alpine.data('tataLetak', () => ({
    sidebarDesktop: true,
    drawerMobile: false,

    alihkanSidebar() {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            this.sidebarDesktop = !this.sidebarDesktop;
        } else {
            this.drawerMobile = !this.drawerMobile;
        }
    },

    tutupDrawer() {
        this.drawerMobile = false;
    },
}));

window.Alpine = Alpine;
Alpine.start();
