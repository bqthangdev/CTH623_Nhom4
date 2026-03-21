import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Compress an image File to below maxSizeMB using Canvas + JPEG re-encoding.
 * Returns the original File unchanged if it is already within the limit.
 */
window._compressImage = async function (file, maxSizeMB = 5) {
    const maxBytes = maxSizeMB * 1024 * 1024;
    if (file.size <= maxBytes) return file;

    return new Promise(resolve => {
        const img = new Image();
        img.onload = function () {
            const canvas = document.createElement('canvas');
            let w = img.naturalWidth, h = img.naturalHeight;
            const maxDim = 2048;
            if (w > maxDim || h > maxDim) {
                const r = Math.min(maxDim / w, maxDim / h);
                w = Math.round(w * r);
                h = Math.round(h * r);
            }
            canvas.width = w;
            canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);

            let quality = 0.85;
            const attempt = () => {
                canvas.toBlob(blob => {
                    if (!blob) { resolve(file); return; }
                    if (blob.size <= maxBytes || quality <= 0.1) {
                        const name = file.name.replace(/\.[^.]+$/, '.jpg');
                        resolve(new File([blob], name, { type: 'image/jpeg' }));
                    } else {
                        quality = Math.max(0.1, parseFloat((quality - 0.1).toFixed(1)));
                        attempt();
                    }
                }, 'image/jpeg', quality);
            };
            attempt();
        };
        img.src = URL.createObjectURL(file);
    });
};

Alpine.start();
