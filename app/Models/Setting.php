<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * In-memory cache for the current request lifecycle
     */
    protected static ?array $runtimeCache = null;

    /**
     * Load all settings into memory and cache (0 queries on subsequent calls)
     */
    public static function allCached(): array
    {
        if (static::$runtimeCache !== null) {
            return static::$runtimeCache;
        }

        try {
            static::$runtimeCache = Cache::rememberForever('app_settings_all', function () {
                return static::pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            static::$runtimeCache = [];
        }

        return static::$runtimeCache;
    }

    public static function getByKey(string $key, ?string $default = null): ?string
    {
        $settings = static::allCached();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function setKey(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        // Invalidate both runtime memory cache and persistent cache
        static::$runtimeCache = null;
        try {
            Cache::forget('app_settings_all');
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function appName(): string
    {
        return static::getByKey('app_name', config('app.name', 'Kosly')) ?: 'Kosly';
    }

    public static function appLogo(): string
    {
        $logoPath = static::getByKey('app_logo');
        if ($logoPath && file_exists(public_path('storage/' . $logoPath))) {
            return asset('storage/' . $logoPath) . '?v=' . filemtime(public_path('storage/' . $logoPath));
        }
        return asset('images/logo.png');
    }

    public static function appFavicon(): string
    {
        $favPath = public_path('favicon.ico');
        if (file_exists($favPath)) {
            return asset('favicon.ico') . '?v=' . filemtime($favPath);
        }
        return static::appLogo();
    }
}
