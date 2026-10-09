export function configurarPantallaCompleta(elemento, boton, claseBody, textoSalida = 'Salir de pantalla completa') {
    let activa = false;
    const actualizar = valor => {
        activa = valor;
        elemento.classList.toggle('is-fullscreen', valor);
        document.body.classList.toggle(claseBody, valor);
        boton.setAttribute('aria-pressed', String(valor));
        boton.textContent = valor ? textoSalida : 'Pantalla completa';
        if (!valor) boton.focus();
    };
    const alternar = async () => {
        if (activa) {
            if (document.fullscreenElement === elemento) await document.exitFullscreen();
            actualizar(false);
            return;
        }
        actualizar(true);
        try {
            await elemento.requestFullscreen?.();
        } catch {
            elemento.classList.add('is-fullscreen');
        }
    };
    const cambio = () => actualizar(document.fullscreenElement === elemento);
    const teclado = evento => {
        if (evento.key === 'Escape' && activa && !document.fullscreenElement) actualizar(false);
    };
    boton.addEventListener('click', alternar);
    document.addEventListener('fullscreenchange', cambio);
    document.addEventListener('keydown', teclado);
    return () => {
        boton.removeEventListener('click', alternar);
        document.removeEventListener('fullscreenchange', cambio);
        document.removeEventListener('keydown', teclado);
        document.body.classList.remove(claseBody);
    };
}
