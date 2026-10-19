import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import puppeteer from 'puppeteer';

const chrome = process.env.CHROME_PATH || (process.platform === 'win32'
    ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : puppeteer.executablePath());

test('el sidebar muestra enlaces directos, conserva Seguridad y abre el menú móvil', {
    skip: !existsSync(chrome), timeout: 30000,
}, async t => {
    let html = '';
    const server = createServer((_request, response) => {
        response.setHeader('Content-Type', 'text/html; charset=utf-8');
        response.end(html);
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    t.after(() => {
        server.closeAllConnections();
        server.close();
    });
    const origin = `http://127.0.0.1:${server.address().port}`;
    const php = String.raw`require 'vendor/autoload.php';
        $app = require 'bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Illuminate\Support\Facades\URL::forceRootUrl($argv[1]);
        $menu = [
            ['type' => 'link', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
            ['type' => 'link', 'label' => 'Seguridad', 'icon' => 'shield', 'children' => [
                ['label' => 'Usuarios', 'route' => 'usuarios.index', 'icon' => 'users'],
                ['label' => 'Roles y permisos', 'route' => 'roles.index', 'icon' => 'shield'],
            ]],
            ['type' => 'link', 'label' => 'Pacientes', 'route' => 'pacientes.index', 'icon' => 'users'],
            ['type' => 'link', 'label' => 'Pacientes remitidos', 'route' => 'pacientes-remitidos.index', 'icon' => 'inbox'],
        ];
        Illuminate\Support\Facades\View::composer('layouts.partials.sidebar', function ($view) use ($menu) {
            $view->with(['sidebarMenu' => $menu, 'sidebarUser' => null]);
        });
        request()->setRouteResolver(fn () => app('router')->getRoutes()->getByName('usuarios.index'));
        preg_match('/<style>(.*?)<\/style>/s', file_get_contents('resources/views/layouts/app.blade.php'), $css);
        echo Illuminate\Support\Facades\Blade::render('<!doctype html><html><head><meta charset="utf-8">'.$css[0].'</head><body><div class="app">@include("layouts.partials.sidebar")<div id="sidebarBackdrop" class="sidebar-backdrop" hidden></div><main class="main-wrapper"><button type="button" class="topbar-mobile-toggle" data-sidebar-open aria-controls="appSidebar" aria-expanded="true">Menú</button></main></div>@stack("scripts")</body></html>');`;
    html = execFileSync('php', ['-r', php, origin], { encoding: 'utf8' });
    const browser = await puppeteer.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
    t.after(async () => {
        await Promise.race([
            browser.close(),
            new Promise(resolve => setTimeout(resolve, 3000)),
        ]);
        if (browser.connected) browser.disconnect();
        browser.process()?.kill();
    });
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.setViewport({ width: 1280, height: 850 });
    await page.goto(origin);
    assert.equal(await page.$eval('#appSidebar', element => element.getBoundingClientRect().width), 260);
    await page.evaluate(() => localStorage.setItem('upea:nav:groups', JSON.stringify({ 'nav-group-seguridad': false })));
    await page.reload();
    assert.equal(await page.$eval('[data-nav-group-id="nav-group-seguridad"] .nav-item-toggle', element => element.getAttribute('aria-expanded')), 'true');
    assert.equal(await page.$eval('#navMenuData', element => JSON.parse(element.textContent).length), 4);
    assert.equal(await page.$$eval('[data-nav-group]', elements => elements.length), 1);
    await page.click('[data-nav-group-id="nav-group-seguridad"] .nav-item-toggle');
    assert.equal(await page.$eval('[data-nav-group-id="nav-group-seguridad"] .nav-item-toggle', element => element.getAttribute('aria-expanded')), 'false');
    await page.evaluate(() => window.upeaSidebar.filter('pacientes'));
    const visible = await page.$$eval('.sidebar-nav a.nav-item', elements => elements.filter(element => element.checkVisibility()).map(element => element.textContent.trim()));
    assert.deepEqual(visible, ['Pacientes', 'Pacientes remitidos']);
    await page.evaluate(() => window.upeaSidebar.filter('sin coincidencias'));
    assert.equal(await page.$eval('[data-nav-filter-empty]', element => element.checkVisibility()), true);
    await page.evaluate(() => window.upeaSidebar.filter(''));
    await page.evaluate(() => window.upeaSidebar.collapseAll());
    await page.click('[data-sidebar-open]');
    await page.waitForFunction(() => Math.round(document.getElementById('appSidebar').getBoundingClientRect().width) === 82, { timeout: 5000 });
    assert.equal(await page.$eval('.app', element => element.classList.contains('sidebar-collapsed')), true);
    assert.equal(await page.$eval('#appSidebar', element => element.getBoundingClientRect().width), 82);
    assert.equal(await page.$eval('.sidebar-nav > .nav-item .nav-text', element => element.checkVisibility()), false);
    assert.equal(await page.$eval('.sidebar-nav > .nav-item .nav-icon', element => element.checkVisibility()), true);
    assert.equal(await page.$eval('[data-sidebar-open]', element => element.getAttribute('aria-label')), 'Expandir menú lateral');
    assert.equal(await page.evaluate(() => localStorage.getItem('upea:sidebar:collapsed')), '1');
    if (process.env.SIDEBAR_SCREENSHOT) await page.screenshot({ path: join(tmpdir(), 'sidebar-contraido.png') });
    await page.reload();
    await page.waitForFunction(() => Math.round(document.getElementById('appSidebar').getBoundingClientRect().width) === 82, { timeout: 5000 });
    assert.equal(await page.$eval('#appSidebar', element => element.getBoundingClientRect().width), 82);
    await page.click('[data-sidebar-open]');
    await page.waitForFunction(() => Math.round(document.getElementById('appSidebar').getBoundingClientRect().width) === 260, { timeout: 5000 });
    assert.equal(await page.$eval('#appSidebar', element => element.getBoundingClientRect().width), 260);
    if (process.env.SIDEBAR_SCREENSHOT) await page.screenshot({ path: join(tmpdir(), 'sidebar-ordenado.png') });
    await page.setViewport({ width: 390, height: 844 });
    await page.waitForFunction(() => window.matchMedia('(max-width: 1024px)').matches, { timeout: 5000 });
    await page.evaluate(() => window.upeaSidebar.open());
    await page.waitForFunction(() => document.getElementById('appSidebar').classList.contains('is-open'), { timeout: 5000 });
    assert.equal(await page.$eval('#appSidebar', element => element.classList.contains('is-open')), true);
    assert.equal(await page.$eval('#appSidebar', element => element.getBoundingClientRect().width), 390);
    assert.equal(await page.$eval('#sidebarBackdrop', element => element.checkVisibility()), true);
    assert.equal(await page.$eval('.main-wrapper', element => element.inert), true);
    await page.waitForFunction(() => document.activeElement === document.querySelector('[data-sidebar-close]'), { timeout: 2000 });
    assert.equal(await page.$eval('[data-sidebar-close]', element => document.activeElement === element), true);
    if (process.env.SIDEBAR_SCREENSHOT) await page.screenshot({ path: join(tmpdir(), 'sidebar-movil.png') });
    await page.keyboard.press('Escape');
    assert.equal(await page.$eval('#sidebarBackdrop', element => element.checkVisibility()), false);
    assert.equal(await page.$eval('.main-wrapper', element => element.inert), false);
    assert.deepEqual(errors, []);
});
