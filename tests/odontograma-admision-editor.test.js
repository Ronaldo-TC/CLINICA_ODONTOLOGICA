import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { extname, resolve, sep } from 'node:path';
import { test } from 'node:test';
import puppeteer from 'puppeteer';

const chrome = process.env.CHROME_PATH || (process.platform === 'win32'
    ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : puppeteer.executablePath());

test('el editor real sincroniza marcas, historial, vistas y pantalla completa con el formulario 2D', {
    skip: !existsSync(chrome) || !existsSync('public/build/manifest.json'), timeout: 120000,
}, async t => {
    const raizPublica = resolve('public');
    let html = '';
    const server = createServer((request, response) => {
        const pathname = new URL(request.url, 'http://localhost').pathname;
        if (pathname === '/') {
            response.setHeader('Content-Type', 'text/html; charset=utf-8');
            response.end(html);
            return;
        }
        const archivo = resolve(raizPublica, '.' + decodeURIComponent(pathname));
        if (!archivo.startsWith(raizPublica + sep) || !existsSync(archivo)) {
            response.writeHead(404).end();
            return;
        }
        response.setHeader('Content-Type', { '.js': 'text/javascript', '.svg': 'image/svg+xml', '.css': 'text/css', '.glb': 'model/gltf-binary', '.woff2': 'font/woff2' }[extname(archivo)] || 'application/octet-stream');
        response.end(readFileSync(archivo));
    });
    await new Promise(resolveListen => server.listen(0, '127.0.0.1', resolveListen));
    t.after(() => new Promise(resolveClose => server.close(resolveClose)));
    const origin = `http://127.0.0.1:${server.address().port}`;
    const plantilla = `<!doctype html><html><head><meta charset="utf-8"><style>
        :root{--border:#cbd5e1;--text:#334155;--bg:#f1f5f9} body{margin:0;font:14px Arial;background:#f1f5f9}
        main{margin:20px 20px 20px 180px} .sidebar{position:fixed;width:160px;height:100vh;background:#234}
        .topbar{padding:16px;background:#fff} .form-grid{display:grid;gap:10px}
        .form-group{display:flex;flex-direction:column;gap:6px} .form-control{padding:6px;min-width:0}
        .btn{padding:7px;border:1px solid #cbd5e1;border-radius:6px;cursor:pointer} .btn:disabled{opacity:.5}
        @media(max-width:640px){main{margin:10px}.sidebar{display:none}}
        </style></head><body><aside class="sidebar">Menú</aside><header class="topbar">Admisiones</header><main><form id="formAdmision">
        <button type="button" id="adm-odontograma-tab-2d" aria-selected="false">Vista 2D</button>
        <button type="button" id="adm-odontograma-tab-3d" aria-selected="true">Vista 3D</button>
        <div id="adm-odontograma-vista-2d" hidden><x-odontograma id="adm-odontograma" /></div>
        <div id="adm-odontograma-vista-3d">@include('admisiones.partials.odontograma-3d')</div>
        </form></main>@stack('styles')@stack('scripts')</body></html>`;
    const php = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); Illuminate\\Support\\Facades\\URL::forceRootUrl($argv[1]); echo Illuminate\\Support\\Facades\\Blade::render($argv[2]);';
    html = execFileSync('php', ['-r', php, origin, plantilla], { encoding: 'utf8' });
    const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'] });
    t.after(() => browser.close());
    const page = await browser.newPage();
    const errores = [];
    page.on('pageerror', error => errores.push(error.message));
    await page.setViewport({ width: 1280, height: 800, deviceScaleFactor: 0.5 });
    await page.goto(origin);
    await page.waitForFunction(() => document.getElementById('adm-3d-status').textContent.startsWith('Modelo listo'), { timeout: 30000 });
    t.diagnostic('Modelo cargado');
    const datos = () => page.$eval('[data-odontograma-input]', input => JSON.parse(input.value));
    await page.select('#adm-3d-pieza', '11');
    await page.select('#adm-3d-vista', 'frontal');
    await page.click('#adm-3d-enfocar');
    const area = await page.$eval('#adm-3d-canvas', element => {
        const rect = element.getBoundingClientRect();
        return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 };
    });
    await page.mouse.click(area.x, area.y);
    const pintadoConClick = await datos();
    assert.ok(Object.values(pintadoConClick).some(diente => Object.values(diente).includes('caries')));
    await page.mouse.click(area.x, area.y, { button: 'right' });
    assert.deepEqual(await datos(), {});
    await page.select('#adm-3d-vista', 'perspectiva');
    await page.select('#adm-3d-pieza', '16');
    await page.click('[data-3d-cara="o"]');
    assert.deepEqual(await datos(), { '16': { o: 'caries' } });
    await page.click('[data-3d-herramienta="rotacion"]');
    await page.select('#adm-3d-color', 'azul');
    await page.click('#adm-3d-aplicar');
    const marcado = { '16': { o: 'caries', marcas: [{ estado: 'rotacion', color: 'azul' }] } };
    assert.deepEqual(await datos(), marcado);
    if (process.env.ODONTOGRAMA_SCREENSHOT) {
        await page.screenshot({ path: resolve(tmpdir(), 'odontograma-admision-adulto.png') });
    }
    await page.click('#adm-3d-deshacer');
    assert.deepEqual(await datos(), { '16': { o: 'caries' } });
    await page.click('#adm-3d-rehacer');
    assert.deepEqual(await datos(), marcado);
    await page.select('#adm-3d-denticion', 'infantil');
    await page.select('#adm-3d-pieza', '55');
    await page.click('[data-3d-herramienta="sellante"]');
    await page.click('[data-3d-cara="v"]');
    assert.deepEqual(await datos(), { ...marcado, '55': { v: 'sellante' } });
    t.diagnostic('Marcado e historial verificados');
    await page.select('#adm-3d-denticion', 'mixto');
    const conservado = await datos();
    await page.click('#adm-3d-pantalla');
    await page.waitForFunction(() => document.getElementById('adm-odontograma-3d').classList.contains('is-fullscreen'));
    const pantalla = await page.evaluate(() => ({
        sidebar: getComputedStyle(document.querySelector('.sidebar')).display,
        topbar: getComputedStyle(document.querySelector('.topbar')).display,
        ancho: document.getElementById('adm-odontograma-3d').getBoundingClientRect().width,
        ventana: innerWidth,
        fondo: getComputedStyle(document.querySelector('.odontograma-3d-stage'), '::before').filter,
    }));
    assert.equal(pantalla.sidebar, 'none');
    assert.equal(pantalla.topbar, 'none');
    assert.equal(pantalla.ancho, pantalla.ventana);
    assert.match(pantalla.fondo, /blur/);
    t.diagnostic('Pantalla completa verificada');
    const canvas = await page.$('#adm-3d-canvas');
    const frente = await canvas.screenshot();
    await page.select('#adm-3d-vista', 'superior');
    await page.waitForFunction(() => document.getElementById('adm-3d-vista').value === 'superior');
    const arriba = await canvas.screenshot();
    assert.notDeepEqual(frente, arriba);
    await page.select('#adm-3d-vista', 'perspectiva');
    await page.click('#adm-3d-acercar');
    await page.click('#adm-3d-alejar');
    assert.deepEqual(await datos(), conservado);
    if (process.env.ODONTOGRAMA_SCREENSHOT) {
        await page.screenshot({ path: resolve(tmpdir(), 'odontograma-admision-3d.png') });
    }
    await page.click('#adm-3d-pantalla');
    assert.deepEqual(await datos(), conservado);
    assert.equal(await page.$eval('.topbar', element => getComputedStyle(element).display), 'block');
    await page.evaluate(() => {
        document.getElementById('adm-odontograma-3d').requestFullscreen = () => Promise.reject(new Error('Fullscreen restringido'));
    });
    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 0.5 });
    await page.click('#adm-3d-pantalla');
    await page.keyboard.press('Escape');
    assert.equal(await page.$eval('#adm-odontograma-3d', element => element.classList.contains('is-fullscreen')), false);
    assert.deepEqual(await datos(), conservado);
    await page.evaluate(() => window.Odontograma.set('#adm-odontograma', { '24': { o: 'obturado' } }, 'adulto'));
    assert.deepEqual(await datos(), { '24': { o: 'obturado' } });
    assert.equal(await page.$eval('#adm-3d-deshacer', boton => boton.disabled), true);
    assert.deepEqual(errores, []);
});
