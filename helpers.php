<?php
declare(strict_types=1);

use RuntimeException;

if (!function_exists('vite_q')) {
    /**
     * Minimal Vite manifest resolver helper.
     * If manifest not found, returns a script tag with given asset path.
     */
    function vite_q(string $asset, string $manifestPath = null): string
    {
        $manifestPath = $manifestPath ?? getcwd() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'manifest.json';

        if (!is_file($manifestPath)) {
            return sprintf('<script src="/%s"></script>', ltrim($asset, '/'));
        }

        $json = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($json)) {
            throw new RuntimeException('Invalid Vite manifest.json');
        }

        $entry = $json[$asset] ?? null;
        if (!$entry) {
            return sprintf('<script src="/%s"></script>', ltrim($asset, '/'));
        }

        $src = $entry['file'] ?? $asset;
        return sprintf('<script type="module" src="/%s"></script>', ltrim($src, '/'));
    }
}
