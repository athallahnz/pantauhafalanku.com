import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/sass/app.scss", "resources/js/app.js"],
            refresh: true,
        }),
    ],

    server: {
        host: "0.0.0.0", // Mengizinkan akses dari luar (HP)
        port: 5173,
        strictPort: true,
        cors: true, // ✅ PENTING: Mencegah Safari memblokir file lintas domain

        hmr: {
            // ✅ Ganti "localhost" dengan DOMAIN NGROK MAS SAAT INI (tanpa https://)
            // Catatan: Kalau Ngrok di-restart dan URL berubah, ini harus di-update lagi ya!
            host: "idioblastic-stetson-recreatively.ngrok-free.dev",

            clientPort: 443, // ✅ Mengelabui browser agar pakai port standar HTTPS Ngrok
            protocol: "wss", // ✅ Menggunakan WebSocket Secure agar Safari tidak marah
        },
    },
});
