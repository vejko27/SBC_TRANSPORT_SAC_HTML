(function () {
  const script = document.currentScript;
  if (!script || !script.src) {
    throw new Error("No se pudo determinar la ubicación de la navegación compartida.");
  }

  const siteRoot = new URL(".", script.src);
  const url = (path) => new URL(path, siteRoot).href;
  const pathname = window.location.pathname.toLowerCase();
  const activePage = pathname.includes("/nosotros/")
    ? "nosotros"
    : pathname.endsWith("/contactenos.html")
      ? "contacto"
      : pathname.includes("/libro_de_reclamaciones/")
        ? "reclamaciones"
        : "inicio";

  const header = document.createElement("header");
  header.className = "site-nav-header";
  header.innerHTML = `
    <div class="site-nav-topbar">
      <div class="site-nav-topbar-inner">
        <a class="site-nav-email" href="mailto:transportes@gruposbc.com.pe">
          <span class="site-nav-email-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path d="M3 5h18v14H3V5Zm1.8 1.5L12 12l7.2-5.5H4.8ZM19.5 17V8.4l-7.1 5.4a.7.7 0 0 1-.8 0L4.5 8.4V17h15Z"/></svg>
          </span>
          <span>transportes@gruposbc.com.pe</span>
        </a>
        <div class="site-nav-phones">
          <a href="tel:+51936804211">Móvil: +51 936 804 211</a>
          <a href="tel:+5117540464">Fijo: 01-7540464</a>
        </div>
      </div>
    </div>
    <nav class="site-nav-main" aria-label="Navegación principal">
      <div class="site-nav-inner">
        <a class="site-nav-brand" href="${url("index.html")}" aria-label="SMARTT, ir al inicio">
          <img src="${url("images/logo/logo.png")}" alt="SMARTT Transport" />
        </a>
        <button
          class="site-nav-toggle"
          id="site-nav-toggle"
          type="button"
          aria-label="Abrir menú"
          aria-controls="site-nav-links"
          aria-expanded="false"
        >
          <span></span><span></span><span></span>
        </button>
        <div class="site-nav-links" id="site-nav-links">
          <a href="${url("index.html")}" data-page="inicio">Inicio</a>
          <a href="${url("contactenos.html")}" data-page="contacto">Contáctenos</a>
          <a href="${url("Nosotros/nosotros.html")}" data-page="nosotros">Nosotros</a>
        </div>
      </div>
    </nav>
  `;

  const activeLink = header.querySelector(`[data-page="${activePage}"]`);
  if (activeLink) {
    activeLink.setAttribute("aria-current", "page");
  }

  const stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.href = `${url("site-nav.css")}?v=2`;
  document.head.appendChild(stylesheet);

  document
    .querySelectorAll("body > .site-topbar, body > .topbar, body > .site-header, body > nav")
    .forEach((existingHeader) => existingHeader.remove());
  document.body.prepend(header);

  const toggle = header.querySelector("#site-nav-toggle");
  const links = header.querySelector("#site-nav-links");

  toggle.addEventListener("click", function () {
    const isExpanded = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!isExpanded));
    toggle.setAttribute("aria-label", isExpanded ? "Abrir menú" : "Cerrar menú");
    links.classList.toggle("is-open", !isExpanded);
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
      toggle.setAttribute("aria-expanded", "false");
      toggle.setAttribute("aria-label", "Abrir menú");
      links.classList.remove("is-open");
      toggle.focus();
    }
  });
})();
