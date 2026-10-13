import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import test from 'node:test';
import puppeteer from 'puppeteer';

const chrome = process.env.CHROME_PATH || (process.platform === 'win32'
    ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : puppeteer.executablePath());
const layout = readFileSync(new URL('../resources/views/layouts/app.blade.php', import.meta.url), 'utf8');
const globals = readFileSync(new URL('../resources/views/layouts/partials/scripts.blade.php', import.meta.url), 'utf8');
const tratamientos = readFileSync(new URL('../resources/views/tratamientos/index.blade.php', import.meta.url), 'utf8');
const usuarios = readFileSync(new URL('../resources/views/usuarios/index.blade.php', import.meta.url), 'utf8');
const styles = layout.match(/<style>([\s\S]*?)<\/style>/)[0];
const modalScript = layout.match(/<script>\s*window\.Modal = ([\s\S]*?)<\/script>/)[0];
const deleteHandler = tratamientos.slice(tratamientos.indexOf('async function eliminar('), tratamientos.indexOf("window.addEventListener('tratamiento:guardado'"));

test('eliminar requiere confirmación, permite cancelar y muestra nombres como texto en móvil y escritorio', {
    skip: !existsSync(chrome), timeout: 30000,
}, async t => {
    const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
    t.after(() => browser.close());
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => {
        errors.push('Se abrió una confirmación nativa');
        await dialog.dismiss();
    });
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8">${styles}</head>
        <body><button id="eliminar" type="button">Eliminar</button>${modalScript}${globals}
        <script>
            const URLS = { destroy: id => '/tratamientos/' + id };
            const CSRF = 'test';
            const setStatus = () => {};
            const toast = () => {};
            const cargarTabla = () => {};
            window.peticiones = [];
            window.fetch = async (url, options) => {
                window.peticiones.push({ url, method: options.method });
                return { ok: true, json: async () => ({ success: true }) };
            };
            ${deleteHandler}
            document.getElementById('eliminar').addEventListener('click', () => {
                window.resultado = eliminar(7, window.nombre || 'Limpieza dental');
            });
        </script></body></html>`);

    await page.click('#eliminar');
    assert.equal(await page.$eval('.confirm-dialog__record', element => element.textContent), 'Limpieza dental');
    assert.equal(await page.$eval('[data-cancel]', element => element === document.activeElement), true);
    assert.deepEqual(await page.evaluate(() => window.peticiones), []);
    await page.click('[data-cancel]');
    await page.evaluate(() => document.querySelector('[data-confirm]')?.click());
    await page.evaluate(() => window.resultado);
    assert.deepEqual(await page.evaluate(() => window.peticiones), []);
    assert.equal(await page.$eval('#eliminar', element => element === document.activeElement), true);

    await page.setViewport({ width: 390, height: 844 });
    const dangerousName = '<img src=x onerror="window.inyectado=true"> Clínica "A"';
    await page.evaluate(nombre => { window.nombre = nombre; }, dangerousName);
    await page.click('#eliminar');
    assert.equal(await page.$eval('.confirm-dialog__record', element => element.textContent), dangerousName);
    assert.equal(await page.$('.confirm-dialog__record img'), null);
    assert.equal(await page.evaluate(() => !!window.inyectado), false);
    assert.equal(await page.$eval('.modal-dynamic', element => element.scrollWidth <= element.clientWidth), true);
    if (process.env.CONFIRM_SCREENSHOT) await page.screenshot({ path: join(tmpdir(), 'confirmacion-eliminar-movil.png') });
    await page.keyboard.press('Escape');
    await page.evaluate(() => window.resultado);
    assert.deepEqual(await page.evaluate(() => window.peticiones), []);

    await page.click('#eliminar');
    await page.click('.modal-close');
    await page.evaluate(() => window.resultado);
    assert.deepEqual(await page.evaluate(() => window.peticiones), []);

    await page.click('#eliminar');
    await page.mouse.click(5, 5);
    await page.evaluate(() => window.resultado);
    assert.deepEqual(await page.evaluate(() => window.peticiones), []);

    await page.setViewport({ width: 1280, height: 850 });
    await page.evaluate(() => { window.nombre = 'Limpieza dental'; });
    await page.click('#eliminar');
    await page.keyboard.press('Tab');
    assert.equal(await page.$eval('[data-confirm]', element => element === document.activeElement), true);
    await page.keyboard.press('Tab');
    assert.equal(await page.$eval('.modal-close', element => element === document.activeElement), true);
    await page.keyboard.down('Shift');
    await page.keyboard.press('Tab');
    await page.keyboard.up('Shift');
    assert.equal(await page.$eval('[data-confirm]', element => element === document.activeElement), true);
    if (process.env.CONFIRM_SCREENSHOT) await page.screenshot({ path: join(tmpdir(), 'confirmacion-eliminar-escritorio.png') });
    await page.click('[data-confirm]');
    await page.evaluate(() => window.resultado);
    assert.deepEqual(await page.evaluate(() => window.peticiones), [{ url: '/tratamientos/7', method: 'DELETE' }]);
    await page.waitForFunction(() => !document.querySelector('.modal-dynamic-backdrop'));

    await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));

    await page.evaluate(() => {
        window.resultado = window.confirmarEliminacion('Usuario de prueba', { deshabilitar: true });
    });
    assert.equal(await page.$eval('[data-confirm]', element => element.textContent), 'Sí, deshabilitar');
    assert.match(await page.$eval('.confirm-dialog__text', element => element.textContent), /Podrás reactivarlo después/);
    await page.click('[data-cancel]');
    assert.equal(await page.evaluate(() => window.resultado), false);
    assert.deepEqual(errors, []);
});

test('Usuarios confirma habilitar, deshabilitar y eliminar con diseños y peticiones diferentes', {
    skip: !existsSync(chrome), timeout: 30000,
}, async t => {
    const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
    t.after(() => browser.close());
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => {
        errors.push('Se abrió una confirmación nativa');
        await dialog.dismiss();
    });
    const handlers = usuarios.slice(usuarios.indexOf("document.addEventListener('click', (e)"), usuarios.indexOf('// El modal emite este evento'));
    await page.setContent(`<!doctype html><html><head><meta charset="utf-8">${styles}</head><body>
        <div class="actions">
            <button class="btn btn-primary" data-action="habilitar" data-id="7" data-nombre="Ana Pérez">Habilitar</button>
            <button class="btn btn-warning" data-action="deshabilitar" data-id="7" data-nombre="Ana Pérez">Deshabilitar</button>
            <button class="btn btn-danger" data-action="eliminar" data-id="7" data-nombre="Ana Pérez">Eliminar</button>
        </div>${modalScript}${globals}<script>
            const URLS = {
                habilitar: id => '/usuarios/' + id + '/habilitar',
                destroy: id => '/usuarios/' + id,
                eliminar: id => '/usuarios/' + id + '/eliminar',
            };
            const CSRF = 'test';
            const setStatus = () => {};
            const toast = () => {};
            const cargarTabla = () => {};
            window.peticiones = [];
            window.fetch = async (url, options) => {
                window.peticiones.push({ url, method: options.method });
                return { ok: true, json: async () => ({ success: true }) };
            };
            ${handlers}
        </script></body></html>`);

    const actions = [
        { name: 'habilitar', variant: 'success', url: '/usuarios/7/habilitar', method: 'POST' },
        { name: 'deshabilitar', variant: 'warning', url: '/usuarios/7', method: 'DELETE' },
        { name: 'eliminar', variant: 'danger', url: '/usuarios/7/eliminar', method: 'DELETE' },
    ];
    for (const [index, action] of actions.entries()) {
        await page.click(`[data-action="${action.name}"]`);
        assert.equal(await page.$eval('[data-confirm]', element => element.textContent), `Sí, ${action.name}`);
        assert.equal(await page.$eval('.confirm-dialog__record', element => element.textContent), 'Ana Pérez');
        assert.notEqual(await page.$(`.confirm-dialog--${action.variant}`), null);
        await page.click('[data-cancel]');
        await page.waitForFunction(() => !document.querySelector('.modal-dynamic-backdrop'));
        assert.equal(await page.evaluate(() => window.peticiones.length), index);

        await page.click(`[data-action="${action.name}"]`);
        if (process.env.CONFIRM_SCREENSHOT) {
            await page.evaluate(() => Promise.all(document.getAnimations().map(animation => animation.finished)));
            await page.screenshot({ path: join(tmpdir(), `usuarios-confirmar-${action.name}.png`) });
        }
        await page.click('[data-confirm]');
        await page.waitForFunction(() => !document.querySelector('.modal-dynamic-backdrop'));
        assert.deepEqual(await page.evaluate(() => window.peticiones.at(-1)), { url: action.url, method: action.method });
    }
    assert.equal(await page.evaluate(() => window.peticiones.length), 3);
    assert.deepEqual(errors, []);
});
