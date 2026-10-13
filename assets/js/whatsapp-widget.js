(function () {
  const script = document.currentScript;
  if (!script || !script.src) {
    throw new Error("No se pudo determinar la ubicación del botón de WhatsApp.");
  }

  const stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.href = `${new URL("../css/whatsapp-widget.css", script.src).href}?v=2`;
  document.head.appendChild(stylesheet);

  const link = document.createElement("a");
  link.className = "whatsapp-widget";
  link.href = "https://wa.me/51936804211";
  link.target = "_blank";
  link.rel = "noopener noreferrer";
  link.setAttribute("aria-label", "Escríbenos por WhatsApp");
  link.innerHTML = `
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M20.5 3.5A11.9 11.9 0 0 0 12 .01C5.4.01.04 5.36.04 11.95c0 2.1.55 4.15 1.6 5.96L0 24l6.25-1.64a12 12 0 0 0 5.74 1.46h.01c6.6 0 11.96-5.35 11.96-11.94 0-3.2-1.24-6.2-3.46-8.38ZM12 21.8h-.01a9.9 9.9 0 0 1-5.04-1.38l-.36-.21-3.71.97.99-3.61-.24-.38a9.85 9.85 0 0 1-1.52-5.24C2.11 6.46 6.55 2.02 12 2.02a9.84 9.84 0 0 1 7.02 2.92 9.82 9.82 0 0 1 2.9 6.98c0 5.45-4.44 9.88-9.92 9.88Zm5.44-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.47-.89-.79-1.5-1.76-1.67-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.87 1.22 3.07c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2-1.42.25-.69.25-1.29.18-1.41-.08-.13-.28-.2-.58-.35Z"/>
    </svg>
    <span>Escríbenos</span>
  `;

  document.body.appendChild(link);
})();
