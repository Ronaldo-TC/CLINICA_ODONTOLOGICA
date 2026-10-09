import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { extname, resolve, sep } from 'node:path';
import { test } from 'node:test';
import puppeteer from 'puppeteer';

const chrome = process.env.CHROME_PATH || (process.platform === 'win32' ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : puppeteer.executablePath());

function estudioDicom() {
    const tag = (group, element, vr, value) => {
        let data;
        if (vr === 'US') { data = Buffer.alloc(2); data.writeUInt16LE(value); }
        else if (vr === 'UL') { data = Buffer.alloc(4); data.writeUInt32LE(value); }
        else data = Buffer.isBuffer(value) ? value : Buffer.from(String(value));
        if (data.length % 2) data = Buffer.concat([data, Buffer.from(vr === 'UI' ? [0] : [32])]);
        const largo = ['OB', 'OW'].includes(vr), header = Buffer.alloc(largo ? 12 : 8);
        header.writeUInt16LE(group, 0); header.writeUInt16LE(element, 2); header.write(vr, 4);
        if (largo) header.writeUInt32LE(data.length, 8); else header.writeUInt16LE(data.length, 6);
        return Buffer.concat([header, data]);
    };
    const pixels = Buffer.alloc(64 * 64 * 3 * 2);
    for (let i = 0; i < pixels.length / 2; i++) pixels.writeUInt16LE((i % 4096) + Math.floor(i / 4096) * 100, i * 2);
    const meta = [tag(2, 1, 'OB', Buffer.from([0, 1])), tag(2, 2, 'UI', '1.2.840.10008.5.1.4.1.1.7.3'),
        tag(2, 3, 'UI', '1.2.826.0.1.3680043.10.999.1'), tag(2, 16, 'UI', '1.2.840.10008.1.2.1'), tag(2, 18, 'UI', '1.2.826.0.1.3680043.10.999.4')];
    return Buffer.concat([
        Buffer.alloc(128), Buffer.from('DICM'),
        tag(2, 0, 'UL', meta.reduce((total, entrada) => total + entrada.length, 0)), ...meta,
        tag(8, 22, 'UI', '1.2.840.10008.5.1.4.1.1.7.3'), tag(8, 24, 'UI', '1.2.826.0.1.3680043.10.999.1'), tag(8, 96, 'CS', 'CT'),
        tag(32, 14, 'UI', '1.2.826.0.1.3680043.10.999.2'), tag(32, 19, 'IS', '1'),
        tag(32, 50, 'DS', '0\\0\\0'), tag(32, 55, 'DS', '1\\0\\0\\0\\1\\0'), tag(32, 82, 'UI', '1.2.826.0.1.3680043.10.999.3'),
        tag(40, 2, 'US', 1), tag(40, 4, 'CS', 'MONOCHROME2'), tag(40, 8, 'IS', '3'),
        tag(40, 16, 'US', 64), tag(40, 17, 'US', 64), tag(40, 48, 'DS', '0.5\\0.5'),
        tag(40, 256, 'US', 16), tag(40, 257, 'US', 16), tag(40, 258, 'US', 15), tag(40, 259, 'US', 0),
        tag(40, 4176, 'DS', '2000'), tag(40, 4177, 'DS', '4000'), tag(40, 4178, 'DS', '0'), tag(40, 4179, 'DS', '1'),
        tag(32736, 16, 'OW', pixels),
    ]);
}

test('el visor real abre PNG y DICOM, conserva la vista en pantalla completa y permite navegar y medir', {
    skip: !existsSync(chrome) || !existsSync('public/build/manifest.json'), timeout: 120000,
}, async t => {
    const publico = resolve('public');
    let html = '';
    const dicom = estudioDicom();
    const server = createServer((request, response) => {
        const pathname = new URL(request.url, 'http://localhost').pathname;
        if (pathname === '/') { response.setHeader('Content-Type', 'text/html; charset=utf-8'); response.end(html); return; }
        if (pathname === '/prueba.dcm') { response.setHeader('Content-Type', 'application/dicom'); response.end(dicom); return; }
        if (pathname === '/prueba.png') {
            response.setHeader('Content-Type', 'image/png');
            response.end(Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zl1sAAAAASUVORK5CYII=', 'base64')); return;
        }
        const file = resolve(publico, '.' + decodeURIComponent(pathname));
        if (!file.startsWith(publico + sep) || !existsSync(file)) { response.writeHead(404).end(); return; }
        response.setHeader('Content-Type', { '.js': 'text/javascript', '.css': 'text/css', '.wasm': 'application/wasm' }[extname(file)] || 'application/octet-stream');
        response.end(readFileSync(file));
    });
    await new Promise(done => server.listen(0, '127.0.0.1', done));
    t.after(() => new Promise(done => server.close(done)));
    const origin = `http://127.0.0.1:${server.address().port}`;
    const php = `require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
        Illuminate\\Support\\Facades\\URL::forceRootUrl($argv[1]); $s=file_get_contents("resources/views/radiologia/visor.blade.php");
        preg_match('/<section id="visor-radiologia".*?<\\/section>/s',$s,$html); preg_match('/<style>.*?<\\/style>/s',$s,$css);
        $plantilla='<!doctype html><html><head><style>body{margin:0;font:14px Arial;background:#f1f5f9}main{margin:20px 20px 20px 160px}.sidebar{position:fixed;width:140px;height:100vh}.topbar{padding:10px}.btn{padding:8px}.form-control{padding:6px}.radiologia-visor-stage img{min-height:100px}</style></head><body><aside class="sidebar">Menú</aside><header class="topbar">Radiología</header><main>'.$html[0].'</main>'.$css[0].'@vite("resources/js/radiologia-visor.js")</body></html>';
        echo Illuminate\\Support\\Facades\\Blade::render($plantilla,['registro'=>new App\\Models\\Radiologia(['id'=>99]),'imagenes'=>collect([
            ['id'=>1,'nombre'=>'Primera radiografía','dicom'=>false,'url'=>$argv[1].'/prueba.png'],
            ['id'=>2,'nombre'=>'Segunda radiografía','dicom'=>false,'url'=>$argv[1].'/prueba.png'],
            ['id'=>3,'nombre'=>'Serie de prueba','dicom'=>true,'url'=>$argv[1].'/prueba.dcm']
        ])]);`;
    html = execFileSync('php', ['-r', php, origin], { encoding: 'utf8' });
    const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'] });
    t.after(() => browser.close());
    const page = await browser.newPage(), errores = [];
    page.on('pageerror', failure => errores.push(failure.message));
    await page.setViewport({ width: 1280, height: 900, deviceScaleFactor: 0.5 });
    await page.goto(origin);
    await page.waitForFunction(() => document.getElementById('dicom-estado').textContent === 'Primera radiografía');
    await page.click('#dicom-girar-derecha'); await page.click('#dicom-acercar'); await page.click('#dicom-invertir');
    const transformacion = await page.$eval('#radiografia-imagen', img => ({ transform: img.style.transform, filter: img.style.filter }));
    assert.match(transformacion.transform, /rotate\(90deg\).*1.25/); assert.match(transformacion.filter, /invert\(1\)/);
    await page.click('#dicom-pantalla');
    assert.equal(await page.$eval('.sidebar', element => getComputedStyle(element).display), 'none');
    assert.equal(await page.$eval('.topbar', element => getComputedStyle(element).display), 'none');
    assert.deepEqual(await page.$eval('#radiografia-imagen', img => ({ transform: img.style.transform, filter: img.style.filter })), transformacion);
    await page.click('#dicom-pantalla');
    await page.click('#dicom-siguiente');
    await page.waitForFunction(() => document.getElementById('dicom-estado').textContent === 'Segunda radiografía');
    await page.waitForFunction(() => document.getElementById('dicom-serie').options.length === 3).catch(async failure => {
        throw new Error(`${failure.message}: ${await page.$eval('#dicom-estado', element => element.textContent)}; ${errores.join('; ')}`);
    });
    const key = await page.$eval('#dicom-serie', select => select.options[2].value);
    await page.select('#dicom-serie', key);
    await page.waitForFunction(() => document.getElementById('dicom-contador').textContent === 'Corte 1 / 3').catch(async failure => {
        throw new Error(`${failure.message}: ${await page.$eval('#dicom-estado', element => element.textContent)}; ${errores.join('; ')}`);
    });
    await page.click('#dicom-siguiente');
    await page.waitForFunction(() => document.getElementById('dicom-contador').textContent === 'Corte 2 / 3');
    await page.select('#dicom-ventana', 'hueso');
    assert.equal(await page.$eval('#dicom-ww', input => input.value), '2000');
    assert.equal(await page.$eval('#dicom-wl', input => input.value), '500');
    await page.click('#dicom-girar-derecha'); await page.click('#dicom-espejo-horizontal');
    await page.click('[data-tool="Length"]');
    const area = await page.$eval('#dicom-viewport', element => { const rect=element.getBoundingClientRect(); return { x:rect.left+rect.width/2,y:rect.top+rect.height/2 }; });
    await page.mouse.move(area.x - 30, area.y); await page.mouse.down(); await page.mouse.move(area.x + 30, area.y, { steps: 6 }); await page.mouse.up();
    await page.waitForFunction(() => document.getElementById('dicom-viewport').textContent.includes('mm'));
    await page.click('[data-tool="Angle"]'); await page.click('[data-tool="RectangleROI"]');
    await page.click('#dicom-pantalla');
    assert.equal(await page.$eval('#dicom-corte', input => input.value), '1');
    assert.equal(await page.$eval('#dicom-ww', input => input.value), '2000');
    assert.equal(await page.$eval('#dicom-wl', input => input.value), '500');
    assert.ok(await page.$eval('#dicom-viewport', element => element.textContent.includes('mm')));
    if (process.env.RADIOLOGIA_SCREENSHOT) await page.screenshot({ path: resolve(tmpdir(), 'radiologia-visor.png') });
    await page.click('#dicom-pantalla');
    await page.click('#dicom-reproducir');
    await page.waitForFunction(() => document.getElementById('dicom-corte').value !== '1');
    await page.click('#dicom-reproducir');
    assert.equal(await page.$eval('#dicom-reproducir', button => button.getAttribute('aria-pressed')), 'false');
    await page.evaluate(() => document.getElementById('visor-radiologia').requestFullscreen = () => Promise.reject(new Error('Restringido')));
    await page.setViewport({ width: 390, height: 844, deviceScaleFactor: 0.5 });
    await page.click('#dicom-pantalla'); await page.keyboard.press('Escape');
    assert.equal(await page.$eval('#visor-radiologia', element => element.classList.contains('is-fullscreen')), false);
    assert.deepEqual(errores, []);
});
