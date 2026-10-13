(function () {
  const script = document.currentScript;
  if (!script || !script.src) {
    throw new Error("No se pudo determinar la ubicación del footer compartido.");
  }

  const siteRoot = new URL("../../", script.src);
  const asset = (path) => new URL(path, siteRoot).href;
  const stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.href = `${asset("assets/css/site-footer.css")}?v=3`;
  document.head.appendChild(stylesheet);

  const footer = document.createElement("footer");
  footer.className = "site-footer";
  footer.innerHTML = `
    <div class="site-footer-main">
      <section aria-labelledby="footer-company-title">
        <h2 id="footer-company-title" class="site-footer-visually-hidden">Sobre SBC Transport</h2>
        <img
          class="footer-company-logo"
          src="${asset("images/logo/logo.png")}"
          alt="SMARTT, transporte de carga pesada"
          loading="lazy"
        />
        <p class="footer-about-copy">
          Ofrecemos servicios de transporte seguros y eficientes para atender las necesidades logísticas de tu empresa.
        </p>
        <p class="footer-legal-name">SBC TRANSPORT SAC</p>
      </section>

      <div class="footer-services-placeholder" aria-hidden="true"></div>

      <section aria-labelledby="footer-quote-title">
        <h2 id="footer-quote-title" class="footer-column-title">Solicita una cotización</h2>
        <a class="footer-quote-number" href="tel:+51936804211">
          <span class="footer-phone-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1A17.5 17.5 0 0 1 2.9 4.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.2 1l-2.2 2.2Z"/></svg>
          </span>
          <span>+51 936 804 211</span>
        </a>
        <p class="footer-quote-copy">
          Llámanos y conversemos sobre el servicio que necesitas.
        </p>
      </section>

      <section aria-labelledby="footer-contact-title">
        <h2 id="footer-contact-title" class="footer-column-title">Información de contacto</h2>
        <ul class="footer-contact-list">
          <li class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false"><path d="M12 22s8-7.2 8-13a8 8 0 1 0-16 0c0 5.8 8 13 8 13Zm0-10.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z"/></svg>
            </span>
            <span>Av. Elmer Faucett 6000, Callao, Perú</span>
          </li>
          <li class="footer-contact-phone-item">
            <span>
              <a class="footer-contact-phone" href="tel:+51936804211">
                <span class="footer-phone-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1A17.5 17.5 0 0 1 2.9 4.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.2 1l-2.2 2.2Z"/></svg>
                </span>
                <span>+51 936 804 211</span>
              </a>
              <a class="footer-contact-phone" href="tel:+5117540464">
                <span class="footer-phone-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.2 11.4 11.4 0 0 0 3.6.6 1 1 0 0 1 1 1v3.5a1 1 0 0 1-1 1A17.5 17.5 0 0 1 2.9 4.5a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .6 3.6 1 1 0 0 1-.2 1l-2.2 2.2Z"/></svg>
                </span>
                <span>01-7540464</span>
              </a>
            </span>
          </li>
          <li class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" focusable="false"><path d="M3 5h18v14H3V5Zm1.8 1.5L12 12l7.2-5.5H4.8ZM19.5 17V8.4l-7.1 5.4a.7.7 0 0 1-.8 0L4.5 8.4V17h15Z"/></svg>
            </span>
            <a href="mailto:transportes@gruposbc.com.pe">transportes@gruposbc.com.pe</a>
          </li>
        </ul>
        <a
          class="complaints-link"
          href="${asset("Libro_de_Reclamaciones/lreclamaciones_View.php")}"
          aria-label="Abrir el Libro de Reclamaciones"
        >
          <img src="${asset("images/Libro_de_reclamaciones_2.png")}" alt="" loading="lazy" />
          <span class="complaints-link-copy">
            <strong>Libro de Reclamaciones</strong>
            <span>Registra una queja o un reclamo <span aria-hidden="true">→</span></span>
          </span>
        </a>
      </section>
    </div>
    <div class="site-footer-bottom">
      <p class="site-footer-copyright">
        SBC TRANSPORT SAC | © 2024 Todos los derechos reservados.
      </p>
    </div>
  `;

  const previousFooter = document.querySelector("footer");
  if (previousFooter) {
    previousFooter.replaceWith(footer);
  } else {
    document.body.appendChild(footer);
  }
})();
