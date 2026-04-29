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
        host: "0.0.0.0", // Tetap biarkan agar bisa diakses IP HP jika satu WiFi
        port: 5173,
        strictPort: true,
        cors: true,

        hmr: {
            // Karena tidak pakai Ngrok, gunakan localhost atau IP lokal
            host: "localhost",
            // Gunakan port asli Vite (5173), bukan port HTTPS (443)
            clientPort: 5173,
            // Gunakan ws (WebSocket), bukan wss (Secure)
            protocol: "ws",
        },
    },
});
