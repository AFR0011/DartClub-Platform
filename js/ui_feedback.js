(function () {
  if (window.AppUI) {
    return;
  }

  const STYLE_ID = "appui-feedback-style";
  const TOAST_HOST_ID = "appui-toast-host";
  let activeDialog = null;

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) {
      return;
    }

    const style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent = `
      #${TOAST_HOST_ID} {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        display: grid;
        gap: 0.75rem;
        z-index: 2500;
        width: min(360px, calc(100vw - 2rem));
      }

      .appui-toast {
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 0.95rem 1rem;
        color: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.28);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(2, 6, 23, 0.98));
        animation: appui-toast-in 160ms ease;
      }

      .appui-toast[data-tone="success"] {
        border-color: rgba(34, 197, 94, 0.34);
        background: linear-gradient(180deg, rgba(12, 44, 24, 0.98), rgba(7, 29, 17, 0.98));
      }

      .appui-toast[data-tone="warning"] {
        border-color: rgba(245, 158, 11, 0.34);
        background: linear-gradient(180deg, rgba(68, 39, 8, 0.98), rgba(46, 27, 7, 0.98));
      }

      .appui-toast[data-tone="error"] {
        border-color: rgba(239, 68, 68, 0.34);
        background: linear-gradient(180deg, rgba(70, 16, 16, 0.98), rgba(48, 10, 10, 0.98));
      }

      .appui-toast[data-tone="info"] {
        border-color: rgba(96, 165, 250, 0.34);
        background: linear-gradient(180deg, rgba(18, 36, 66, 0.98), rgba(9, 22, 46, 0.98));
      }

      .appui-overlay {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(3, 8, 18, 0.72);
        backdrop-filter: blur(10px);
        z-index: 2600;
      }

      .appui-dialog {
        width: min(520px, 100%);
        display: grid;
        gap: 1rem;
        padding: 1.3rem;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        background:
          radial-gradient(circle at top right, rgba(255, 124, 77, 0.18), transparent 32%),
          linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(6, 11, 22, 0.98));
        box-shadow: 0 28px 60px rgba(15, 23, 42, 0.42);
        color: #fff;
      }

      .appui-dialog h3 {
        margin: 0;
        font-size: 1.2rem;
      }

      .appui-dialog p {
        margin: 0;
        color: rgba(226, 232, 240, 0.88);
        line-height: 1.55;
      }

      .appui-dialog textarea,
      .appui-dialog input {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        padding: 0.9rem 1rem;
        font: inherit;
      }

      .appui-dialog textarea {
        min-height: 140px;
        resize: vertical;
      }

      .appui-dialog textarea:focus,
      .appui-dialog input:focus {
        outline: none;
        border-color: rgba(255, 124, 77, 0.42);
        box-shadow: 0 0 0 4px rgba(255, 124, 77, 0.12);
      }

      .appui-dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
      }

      .appui-button,
      .appui-button-secondary,
      .appui-button-danger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-width: 120px;
        padding: 0.82rem 1rem;
        border-radius: 999px;
        border: 1px solid transparent;
        cursor: pointer;
        font: inherit;
        transition: transform 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
      }

      .appui-button {
        background: linear-gradient(180deg, #ff7c4d, #ef5a29);
        color: #fff;
      }

      .appui-button-secondary {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.12);
        color: #fff;
      }

      .appui-button-danger {
        background: rgba(220, 38, 38, 0.14);
        border-color: rgba(220, 38, 38, 0.26);
        color: #fecaca;
      }

      .appui-button:hover,
      .appui-button-secondary:hover,
      .appui-button-danger:hover {
        transform: translateY(-1px);
      }

      @keyframes appui-toast-in {
        from {
          opacity: 0;
          transform: translateY(8px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @media (max-width: 640px) {
        .appui-dialog-actions {
          display: grid;
          grid-template-columns: 1fr;
        }

        .appui-button,
        .appui-button-secondary,
        .appui-button-danger {
          width: 100%;
        }
      }
    `;

    document.head.appendChild(style);
  }

  function getToastHost() {
    ensureStyles();

    let host = document.getElementById(TOAST_HOST_ID);
    if (!host) {
      host = document.createElement("div");
      host.id = TOAST_HOST_ID;
      document.body.appendChild(host);
    }

    return host;
  }

  function toast(message, tone = "info", duration = 3600) {
    const host = getToastHost();
    const item = document.createElement("div");
    item.className = "appui-toast";
    item.dataset.tone = tone;
    item.textContent = String(message || "");
    host.appendChild(item);

    window.setTimeout(() => {
      item.remove();
      if (!host.childElementCount) {
        host.remove();
      }
    }, Math.max(1200, Number(duration) || 3600));
  }

  function closeActiveDialog(value) {
    if (!activeDialog) {
      return;
    }

    const { overlay, resolve, cleanup } = activeDialog;
    activeDialog = null;
    cleanup();
    overlay.remove();
    resolve(value);
  }

  function buttonClassForTone(tone) {
    return tone === "danger" || tone === "error" ? "appui-button-danger" : "appui-button";
  }

  function openDialog(config, mode) {
    ensureStyles();

    return new Promise((resolve) => {
      if (activeDialog) {
        closeActiveDialog(mode === "confirm" ? false : null);
      }

      const overlay = document.createElement("div");
      overlay.className = "appui-overlay";

      const dialog = document.createElement("div");
      dialog.className = "appui-dialog";
      dialog.setAttribute("role", "dialog");
      dialog.setAttribute("aria-modal", "true");
      dialog.setAttribute("aria-label", String(config.title || config.message || "Dialog"));

      const title = document.createElement("h3");
      title.textContent = String(config.title || "Please confirm");
      dialog.appendChild(title);

      if (config.message) {
        const message = document.createElement("p");
        message.textContent = String(config.message);
        dialog.appendChild(message);
      }

      let field = null;
      if (mode === "prompt") {
        field = config.multiline ? document.createElement("textarea") : document.createElement("input");
        if (!config.multiline) {
          field.type = "text";
        }
        field.value = String(config.initialValue || "");
        if (config.placeholder) {
          field.placeholder = String(config.placeholder);
        }
        dialog.appendChild(field);
      }

      const actions = document.createElement("div");
      actions.className = "appui-dialog-actions";

      const cancelButton = document.createElement("button");
      cancelButton.type = "button";
      cancelButton.className = "appui-button-secondary";
      cancelButton.textContent = String(config.cancelLabel || "Cancel");
      cancelButton.addEventListener("click", () => closeActiveDialog(mode === "confirm" ? false : null));
      actions.appendChild(cancelButton);

      const confirmButton = document.createElement("button");
      confirmButton.type = "button";
      confirmButton.className = buttonClassForTone(String(config.tone || "primary"));
      confirmButton.textContent = String(config.confirmLabel || (mode === "prompt" ? "Save" : "Confirm"));
      confirmButton.addEventListener("click", () => {
        closeActiveDialog(mode === "prompt" ? field.value : true);
      });
      actions.appendChild(confirmButton);

      dialog.appendChild(actions);
      overlay.appendChild(dialog);
      document.body.appendChild(overlay);

      const handleOverlayClick = (event) => {
        if (event.target === overlay) {
          closeActiveDialog(mode === "confirm" ? false : null);
        }
      };

      const handleKeyDown = (event) => {
        if (event.key === "Escape") {
          event.preventDefault();
          closeActiveDialog(mode === "confirm" ? false : null);
          return;
        }

        if (mode === "prompt" && event.key === "Enter" && !config.multiline) {
          event.preventDefault();
          closeActiveDialog(field.value);
        }
      };

      overlay.addEventListener("click", handleOverlayClick);
      document.addEventListener("keydown", handleKeyDown);

      activeDialog = {
        overlay,
        resolve,
        cleanup() {
          overlay.removeEventListener("click", handleOverlayClick);
          document.removeEventListener("keydown", handleKeyDown);
        },
      };

      window.requestAnimationFrame(() => {
        if (field) {
          field.focus();
          field.select?.();
        } else {
          confirmButton.focus();
        }
      });
    });
  }

  window.AppUI = {
    toast,
    confirm(config = {}) {
      return openDialog(config, "confirm");
    },
    prompt(config = {}) {
      return openDialog(config, "prompt");
    },
  };
})();
