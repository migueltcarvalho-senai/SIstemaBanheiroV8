/**
 * UI.js - Sistema Global de Micro-interações e Transições (v2.1)
 * Responsável por efeitos visuais e transições sincronizadas de página.
 */

document.addEventListener("DOMContentLoaded", () => {
    // 1. Animação de Entrada (Fade-in)
    // Pequeno delay para garantir que o browser renderizou o estado inicial
    setTimeout(() => {
        document.body.classList.add("page-ready");
    }, 50);

    // 2. Configura Ripples nos Botões
    initRippleEffect();

    // 3. Configura Ripples e Transição de Saída na Navegação
    initNavigation();
});

/**
 * Especial: Gerencia a navegação com animação de saída
 */
function initNavigation() {
    const navLinks = document.querySelectorAll("header nav a");
    const header = document.querySelector("header");

    navLinks.forEach(link => {
        link.addEventListener("click", (event) => {
            const href = link.getAttribute("href");
            
            // Verifica se é um link interno válido
            if (href && !href.startsWith("#") && href !== window.location.pathname.split('/').pop()) {
                event.preventDefault(); // Impede a navegação imediata

                // Executa o Ripple no Header
                createRipple(event, header, "rgba(255, 255, 255, 0.2)");

                // Inicia animação de saída no Body
                document.body.classList.remove("page-ready");
                document.body.classList.add("page-exit");

                // Aguarda o tempo da animação (600ms conforme o CSS) para mudar de página
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            } else {
                // Se for a mesma página, apenas o ripple
                createRipple(event, header, "rgba(255, 255, 255, 0.2)");
            }
        });
    });
}

/**
 * Aplica o efeito ripple em todos os botões detectados
 */
function initRippleEffect() {
    const buttons = document.getElementsByTagName("button");
    for (const button of buttons) {
        if (!button.dataset.rippleAttached) {
            button.addEventListener("click", createRipple);
            button.dataset.rippleAttached = "true";
        }
    }
}

/**
 * Lógica genérica de criação de Ripple
 */
function createRipple(event, target = null, color = null) {
    const element = target || event.currentTarget;
    const circle = document.createElement("span");
    
    const diameter = Math.max(element.clientWidth, element.clientHeight);
    const radius = diameter / 2;
    const rect = element.getBoundingClientRect();

    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - rect.left - radius}px`;
    circle.style.top = `${event.clientY - rect.top - radius}px`;
    
    if (color) circle.style.backgroundColor = color;
    circle.classList.add("ripple");

    const oldRipple = element.querySelector(".ripple");
    if (oldRipple && element === target) oldRipple.remove();

    element.appendChild(circle);
    setTimeout(() => circle.remove(), 600);
}
