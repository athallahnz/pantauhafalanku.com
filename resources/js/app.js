// ❌ HAPUS BARIS INI
// import "bootstrap/dist/js/bootstrap.bundle.min.js";

import "./bootstrap";
import { Modal, Tooltip, Sidebar } from "@coreui/coreui";

// expose coreui
window.coreui = { Modal, Tooltip, Sidebar };

// SweetAlert2
import Swal from "sweetalert2";
window.Swal = Swal;

window.AppAlert = {
    success(message = "Berhasil diproses.", title = "Berhasil!") {
        return Swal.fire({
            icon: "success",
            title,
            text: message,
            timer: 1800,
            showConfirmButton: false,
        });
    },
    error(message = "Terjadi kesalahan.", title = "Gagal!") {
        return Swal.fire({
            icon: "error",
            title,
            text: message,
        });
    },
    warning(message = "Yakin dengan tindakan ini?", title = "Perhatian!") {
        return Swal.fire({
            icon: "warning",
            title,
            text: message,
            showCancelButton: true,
            confirmButtonText: "Ya",
            cancelButtonText: "Batal",
        });
    },
};

document.addEventListener("DOMContentLoaded", () => {
    document
        .querySelectorAll('[data-coreui-toggle="tooltip"]')
        .forEach((el) => {
            new window.coreui.Tooltip(el);
        });

    const sidebarEl = document.getElementById("sidebar");

    if (sidebarEl) {
        const sidebarInstance =
            window.coreui.Sidebar.getOrCreateInstance(sidebarEl);

        document
            .querySelectorAll('[data-coreui-toggle="sidebar"]')
            .forEach((btn) => {
                btn.addEventListener("click", (event) => {
                    event.preventDefault();
                    sidebarInstance.toggle();
                });
            });
    }
});
