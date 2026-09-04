<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * @property string|null $link_gmaps
 * @property string|null $alamat
 * @property string|null $nama
 */
trait HasGmapsQuery
{
    /**
     * Dapatkan titik koordinat / nama tempat dari link Google Maps untuk navigasi rute akurat.
     */
    public function getGmapsQueryAttribute(): string
    {
        $url = $this->link_gmaps ?? '';
        $fallback = $this->alamat ?: ($this->nama ?? '');

        if (empty($url) || $url === '-') {
            return $fallback;
        }

        $url = trim($url);

        // 1. Koordinat PIN presisi dari parameter data=!3d... !4d...
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $url, $m)) {
            return "{$m[1]},{$m[2]}";
        }

        // 2. Koordinat langsung pada parameter ?q=lat,lng atau q=loc:lat,lng
        if (preg_match('/[?&]q=(?:loc:)?(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
            return "{$m[1]},{$m[2]}";
        }

        // 3. Tautan pendek Google Maps (maps.app.goo.gl / goo.gl/maps / page.link)
        if (str_contains($url, 'maps.app.goo.gl') || str_contains($url, 'goo.gl/maps') || str_contains($url, 'page.link')) {
            $cacheKey = 'gmaps_resolved_' . md5($url);
            $resolved = Cache::remember($cacheKey, 86400 * 30, function () use ($url) {
                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
                    curl_exec($ch);
                    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                    return $finalUrl ?: $url;
                } catch (\Throwable) {
                    return $url;
                }
            });

            if ($resolved) {
                // Prioritaskan koordinat PIN marker presisi (!3d, !4d)
                if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $resolved, $m)) {
                    return "{$m[1]},{$m[2]}";
                }
                // Koordinat dari parameter ?q=
                if (preg_match('/[?&]q=(?:loc:)?(-?\d+\.\d+),(-?\d+\.\d+)/', $resolved, $m)) {
                    return "{$m[1]},{$m[2]}";
                }
                // Nama tempat / plus code dari /place/
                if (preg_match('/\/place\/([^\/@?]+)/', $resolved, $m)) {
                    return urldecode(str_replace('+', ' ', $m[1]));
                }
                // Fallback terakhir ke viewport center
                if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $resolved, $m)) {
                    return "{$m[1]},{$m[2]}";
                }
            }
        }

        // 4. Nama tempat dari pola /place/Nama+Tempat/
        if (preg_match('/\/place\/([^\/@?]+)/', $url, $m)) {
            return urldecode(str_replace('+', ' ', $m[1]));
        }

        // 5. Query parameter ?q=...
        if (preg_match('/[?&]q=([^&]+)/', $url, $m)) {
            return urldecode($m[1]);
        }

        // 6. Viewport center @lat,lng sebagai fallback terakhir
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $m)) {
            return "{$m[1]},{$m[2]}";
        }

        return $fallback;
    }
}
