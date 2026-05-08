<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class FaviconProxyController extends Controller
{
    private const PATHS = ['/favicon.ico', '/favicon.png', '/apple-touch-icon.png'];

    public function __invoke(Request $request): Response
    {
        $url = $request->query('url');

        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response('', 404);
        }

        $parsed = parse_url($url);
        if (empty($parsed['host'])) {
            return response('', 404);
        }

        $base = ($parsed['scheme'] ?? 'http') . '://' . $parsed['host'];
        if (! empty($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }

        // Tentatives 1-3 : chemins directs classiques
        foreach (self::PATHS as $path) {
            $result = $this->fetchImage($base . $path);
            if ($result) {
                return $result;
            }
        }

        // Tentative 4 : parse le HTML de la page pour trouver <link rel="icon">
        $faviconUrl = $this->extractFaviconFromHtml($url, $base);
        if ($faviconUrl) {
            $result = $this->fetchImage($faviconUrl);
            if ($result) {
                return $result;
            }
        }

        return response('', 404);
    }

    private function fetchImage(string $url): ?Response
    {
        try {
            $res = Http::timeout(5)
                ->withOptions(['verify' => false])
                ->get($url);

            if ($res->ok() && strlen($res->body()) > 0) {
                $contentType = $res->header('Content-Type') ?: 'image/x-icon';

                return response($res->body(), 200)
                    ->header('Content-Type', $contentType)
                    ->header('Cache-Control', 'public, max-age=86400');
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function extractFaviconFromHtml(string $url, string $base): ?string
    {
        try {
            $res = Http::timeout(8)
                ->withOptions(['verify' => false, 'allow_redirects' => true, 'http_errors' => false])
                ->get($url);

            if (! $res->ok()) {
                return null;
            }

            $html = $res->body();

            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();

            $links = $doc->getElementsByTagName('link');

            $candidates = [];
            foreach ($links as $link) {
                $rel = strtolower($link->getAttribute('rel'));
                $href = $link->getAttribute('href');

                if (! $href) {
                    continue;
                }

                // Priorité : icon > shortcut icon > apple-touch-icon > mask-icon
                if ($rel === 'icon') {
                    array_unshift($candidates, $href);
                } elseif (in_array($rel, ['shortcut icon', 'apple-touch-icon', 'apple-touch-icon-precomposed', 'mask-icon'])) {
                    $candidates[] = $href;
                }
            }

            foreach ($candidates as $href) {
                return $this->resolveUrl($href, $base);
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function resolveUrl(string $href, string $base): string
    {
        // URL absolue
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        // URL protocol-relative (//example.com/favicon.ico)
        if (str_starts_with($href, '//')) {
            $scheme = str_starts_with($base, 'https') ? 'https' : 'http';

            return $scheme . ':' . $href;
        }

        // URL relative
        return rtrim($base, '/') . '/' . ltrim($href, '/');
    }
}
