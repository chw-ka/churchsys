<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsivePwaTest extends TestCase
{
    /* --------------------------------------------------------------
     * PWA static assets exist and are well-formed
     * -------------------------------------------------------------- */

    public function test_pwa_static_assets_exist(): void
    {
        foreach ([
            'manifest.json',
            'sw.js',
            'offline.html',
            'css/responsive.css',
            'js/app-responsive.js',
            'icons/icon-192.png',
            'icons/icon-512.png',
            'icons/icon-maskable-192.png',
            'icons/icon-maskable-512.png',
            'icons/apple-touch-icon.png',
            'favicon.ico',
        ] as $file) {
            $this->assertFileExists(public_path($file), "missing public/{$file}");
        }
    }

    public function test_manifest_is_valid_and_installable(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertIsArray($manifest);
        foreach (['name', 'short_name', 'start_url', 'display', 'icons'] as $key) {
            $this->assertArrayHasKey($key, $manifest);
        }
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);

        // Chrome installability: at least one 192px and one 512px icon
        $sizes = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }

    public function test_service_worker_handles_navigations_and_assets(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("addEventListener('install'", $sw);
        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        $this->assertStringContainsString('offline.html', $sw);
        $this->assertStringContainsString("request.mode === 'navigate'", $sw);
    }

    /* --------------------------------------------------------------
     * Layout carries the responsive + PWA hooks
     * -------------------------------------------------------------- */

    public function test_login_page_has_viewport_manifest_and_responsive_assets(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        $html = $response->getContent();
        foreach ([
            'width=device-width, initial-scale=1, viewport-fit=cover',
            'rel="manifest"',
            'name="theme-color"',
            'apple-touch-icon',
            'css/responsive.css',
            'js/app-responsive.js',
            'id="nav-toggle"', // injected by app-responsive.js at runtime? no — assert script tag instead
        ] as $needle) {
            if ($needle === 'id="nav-toggle"') {
                continue; // created dynamically by JS
            }
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_layout_defines_install_menu_item(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('install-item', $layout);
    }

    /* --------------------------------------------------------------
     * Report pages (standalone HTML) are mobile-safe
     * -------------------------------------------------------------- */

    public function test_report_views_have_viewport_and_table_wrapper(): void
    {
        $dir = resource_path('views/worship/reports');
        $views = glob($dir . '/*.blade.php');
        $this->assertNotEmpty($views);

        foreach ($views as $view) {
            $src = file_get_contents($view);
            $this->assertStringContainsString(
                'name="viewport"',
                $src,
                basename($view) . ' missing viewport meta'
            );
            $this->assertStringContainsString(
                'table-scroll',
                $src,
                basename($view) . ' missing table wrapper'
            );
        }
    }

    /* --------------------------------------------------------------
     * Auth flow still works end-to-end (regression guard)
     * -------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
