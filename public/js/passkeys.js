(function () {
    'use strict';

    function ensureSupported() {
        if (!window.isSecureContext || !window.PublicKeyCredential || !navigator.credentials) {
            throw new Error('Browser atau koneksi ini belum mendukung passkey. Gunakan HTTPS atau localhost.');
        }
    }

    function base64urlToBuffer(value) {
        if (typeof value !== 'string' || value.length === 0) {
            throw new Error('Data passkey dari server tidak valid.');
        }

        const padding = '='.repeat((4 - (value.length % 4)) % 4);
        const binary = window.atob((value + padding).replace(/-/g, '+').replace(/_/g, '/'));
        const bytes = new Uint8Array(binary.length);

        for (let index = 0; index < binary.length; index += 1) {
            bytes[index] = binary.charCodeAt(index);
        }

        return bytes.buffer;
    }

    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';

        for (let offset = 0; offset < bytes.length; offset += 0x8000) {
            binary += String.fromCharCode.apply(null, bytes.subarray(offset, offset + 0x8000));
        }

        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function creationOptions(payload) {
        const publicKey = payload && payload.publicKey ? payload.publicKey : payload;
        const normalized = {
            ...publicKey,
            challenge: base64urlToBuffer(publicKey.challenge),
            user: {
                ...publicKey.user,
                id: base64urlToBuffer(publicKey.user.id),
            },
        };

        if (Array.isArray(publicKey.excludeCredentials)) {
            normalized.excludeCredentials = publicKey.excludeCredentials.map((credential) => ({
                ...credential,
                id: base64urlToBuffer(credential.id),
            }));
        }

        return normalized;
    }

    function requestOptions(payload) {
        const publicKey = payload && payload.publicKey ? payload.publicKey : payload;
        const normalized = {
            ...publicKey,
            challenge: base64urlToBuffer(publicKey.challenge),
        };

        if (Array.isArray(publicKey.allowCredentials)) {
            normalized.allowCredentials = publicKey.allowCredentials.map((credential) => ({
                ...credential,
                id: base64urlToBuffer(credential.id),
            }));
        }

        return normalized;
    }

    function serializeCredential(credential) {
        const response = {
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
        };

        if ('attestationObject' in credential.response) {
            response.attestationObject = bufferToBase64url(credential.response.attestationObject);
            response.transports = typeof credential.response.getTransports === 'function'
                ? credential.response.getTransports()
                : [];
        }

        if ('authenticatorData' in credential.response) {
            response.authenticatorData = bufferToBase64url(credential.response.authenticatorData);
            response.signature = bufferToBase64url(credential.response.signature);
            response.userHandle = credential.response.userHandle === null
                ? null
                : bufferToBase64url(credential.response.userHandle);
        }

        return {
            id: credential.id,
            rawId: bufferToBase64url(credential.rawId),
            type: credential.type,
            authenticatorAttachment: credential.authenticatorAttachment || null,
            clientExtensionResults: credential.getClientExtensionResults(),
            response,
        };
    }

    function csrfToken() {
        const element = document.querySelector('meta[name="csrf-token"]');
        if (!element || !element.content) {
            throw new Error('Token keamanan halaman tidak tersedia. Muat ulang halaman.');
        }

        return element.content;
    }

    async function requestJson(url, body) {
        const response = await window.fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body || {}),
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch (_error) {
            payload = {};
        }

        if (!response.ok) {
            const error = new Error(payload.message || 'Permintaan passkey gagal.');
            error.status = response.status;
            error.payload = payload;
            throw error;
        }

        return payload;
    }

    async function register(settings) {
        ensureSupported();

        const options = await requestJson(settings.optionsUrl, {
            name: settings.name,
            current_password: settings.currentPassword,
        });
        const credential = await navigator.credentials.create({
            publicKey: creationOptions(options),
        });

        if (!credential) {
            throw new Error('Pembuatan passkey dibatalkan.');
        }

        return requestJson(settings.verifyUrl, serializeCredential(credential));
    }

    async function authenticate(settings) {
        ensureSupported();

        const options = await requestJson(settings.optionsUrl, {});
        const credential = await navigator.credentials.get({
            publicKey: requestOptions(options),
        });

        if (!credential) {
            throw new Error('Penggunaan passkey dibatalkan.');
        }

        return requestJson(settings.verifyUrl, {
            ...serializeCredential(credential),
            remember: Boolean(settings.remember),
        });
    }

    window.SimtaquPasskeys = {
        authenticate,
        isSupported: function () {
            return Boolean(window.isSecureContext && window.PublicKeyCredential && navigator.credentials);
        },
        register,
    };
}());
