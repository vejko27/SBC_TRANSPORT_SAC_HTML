(function () {
  const script = document.currentScript;
  if (!script || !script.src) {
    throw new Error("No se pudo determinar la ubicación de la navegación compartida.");
  }

  const siteRoot = new URL("../../", script.src);
  const url = (path) => new URL(path, siteRoot).href;
  const pathname = window.location.pathname.toLowerCase();
  const isSocialPolicy = pathname.endsWith("/politicas_sbc/responsabilidad_social_sbc.html");
  const isEthicsCode = pathname.endsWith("/politicas_sbc/codigo_de_etica_y_conducta.html");
  const isSecurityPolicy = pathname.endsWith("/politicas_sbc/politica_de_gestion_de_control_y_seguridad.html");
  const activePage = isSecurityPolicy
    ? "gestion-seguridad"
    : isEthicsCode
      ? "codigo-etica"
    : isSocialPolicy
      ? "responsabilidad-social"
    : pathname.includes("/nosotros/")
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
          <a href="tel:+51936804211">
            <span class="smartt-phone-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1A17.5 17.5 0 0 1 2.9 4.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.2 1l-2.2 2.2Z"/></svg>
            </span>
            <span>Móvil: +51 936 804 211</span>
          </a>
          <a href="tel:+5117540464">
            <span class="smartt-phone-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1A17.5 17.5 0 0 1 2.9 4.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.2 1l-2.2 2.2Z"/></svg>
            </span>
            <span>Fijo: 01-7540464</span>
          </a>
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
          <div class="site-nav-dropdown" id="site-nav-dropdown">
            <a href="${url("Nosotros/nosotros.html")}" data-page="nosotros">Nosotros</a>
            <button
              class="site-nav-submenu-toggle"
              type="button"
              aria-label="Mostrar páginas de Nosotros"
              aria-controls="site-nav-submenu"
              aria-expanded="false"
            ><span aria-hidden="true"></span></button>
            <div class="site-nav-dropdown-menu" id="site-nav-submenu">
              <a href="${url("Politicas_SBC/responsabilidad_social_sbc.html")}" data-page="responsabilidad-social">Responsabilidad Social</a>
              <a href="${url("Politicas_SBC/Codigo_de_Etica_y_Conducta.html")}" data-page="codigo-etica">Código de Ética y Conducta</a>
              <a href="${url("Politicas_SBC/Politica_de_Gestion_de_Control_y_Seguridad.html")}" data-page="gestion-seguridad">Política BASC de Gestión en Control y Seguridad</a>
            </div>
          </div>
        </div>
      </div>
    </nav>
  `;

  const activeLink = header.querySelector(`[data-page="${activePage}"]`);
  if (activeLink) {
    activeLink.setAttribute("aria-current", "page");
  }
  if (isSocialPolicy || isEthicsCode || isSecurityPolicy) {
    header.querySelector(".site-nav-dropdown").classList.add("is-active");
  }

  const stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.href = `${url("assets/css/site-nav.css")}?v=9`;
  document.head.appendChild(stylesheet);

  document
    .querySelectorAll("body > .site-topbar, body > .topbar, body > .site-header, body > nav")
    .forEach((existingHeader) => existingHeader.remove());
  document.body.prepend(header);

  const toggle = header.querySelector("#site-nav-toggle");
  const links = header.querySelector("#site-nav-links");
  const dropdown = header.querySelector("#site-nav-dropdown");
  const submenuToggle = header.querySelector(".site-nav-submenu-toggle");

  toggle.addEventListener("click", function () {
    const isExpanded = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!isExpanded));
    toggle.setAttribute("aria-label", isExpanded ? "Abrir menú" : "Cerrar menú");
    links.classList.toggle("is-open", !isExpanded);
  });

  submenuToggle.addEventListener("click", function () {
    const isExpanded = submenuToggle.getAttribute("aria-expanded") === "true";
    submenuToggle.setAttribute("aria-expanded", String(!isExpanded));
    submenuToggle.setAttribute(
      "aria-label",
      isExpanded ? "Mostrar páginas de Nosotros" : "Ocultar páginas de Nosotros"
    );
    dropdown.classList.toggle("is-open", !isExpanded);
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && submenuToggle.getAttribute("aria-expanded") === "true") {
      submenuToggle.setAttribute("aria-expanded", "false");
      submenuToggle.setAttribute("aria-label", "Mostrar páginas de Nosotros");
      dropdown.classList.remove("is-open");
      submenuToggle.focus();
      return;
    }

    if (event.key === "Escape" && toggle.getAttribute("aria-expanded") === "true") {
      toggle.setAttribute("aria-expanded", "false");
      toggle.setAttribute("aria-label", "Abrir menú");
      links.classList.remove("is-open");
      toggle.focus();
    }
  });
})();
