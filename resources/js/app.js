/* Put the self-hosted fonts in the Vite manifest so the layout can preload
   them by their hashed URLs (Vite::asset). */
import.meta.glob('../fonts/*.woff2');

/* Assemble the mailto at runtime so the address is not sitting in the
   markup for harvesters. Without JS the reversed text still reads correctly
   and the link falls back to the contact block. */
const contact = document.getElementById('contact-email');
if (contact) {
    const address = contact.dataset.u + String.fromCharCode(64) + contact.dataset.d;
    contact.href = 'mailto:' + address;
    contact.textContent = address;
}
