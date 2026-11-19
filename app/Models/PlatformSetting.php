<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * PlatformSetting  -  the platform-wide settings (Super Admin > Settings page).
 *
 * HOW IT WORKS
 *   Each setting is one row: key + text value. You almost never touch the rows directly. Use:
 *       PlatformSetting::all_()            -> ['platformName' => 'Hotel SaaS', 'trialDays' => 14, ...]  (defaults + saved values)
 *       PlatformSetting::put(['trialDays' => 30])   -> saves only the keys you pass
 *   A setting that was never saved simply returns its default from DEFAULTS below.
 *
 * TO ADD A NEW SETTING
 *   1. Add it to DEFAULTS (key => default value). The type of the default (string / int / bool) decides how
 *      the saved text is converted back.
 *   2. Add a validation rule for it in PlatformSettingsController::rules().
 *   3. Add the field to the React form. No migration needed.
 */
class PlatformSetting extends Model
{
    // The primary key is the text `key`, not a number.
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Every known setting and its default value.
     * The API uses these exact (camelCase) names, so the React form fields match them one to one.
     */
    public const DEFAULTS = [
        'platformName' => 'Hotel SaaS',   // shown in e-mails and the login page (TODO: use it there)
        'supportEmail' => '',             // where hotel owners can ask for help
        'supportPhone' => '',
        'defaultCurrency' => 'AFN',       // pre-selected currency on the "register hotel" form
        'defaultPlanId' => 'starter',     // pre-selected plan on the "register hotel" form
        'trialDays' => 14,                // free trial length for new hotels (TODO: enforce when billing exists)
    ];

    /**
     * All settings: the defaults, overwritten by whatever has been saved.
     * (Named all_ because Eloquent already has a static all() method.)
     *
     * @return array<string, string|int|bool>
     */
    public static function all_(): array
    {
        $saved = static::query()->pluck('value', 'key'); // ['trialDays' => '30', ...] (everything is text)
        $result = [];

        foreach (self::DEFAULTS as $key => $default) {
            $result[$key] = $saved->has($key) ? self::castBack($saved[$key], $default) : $default;
        }

        return $result;
    }

    /**
     * Saves the given settings. Unknown keys are ignored, so a client can not store random data.
     * Uses updateOrCreate: update the row if it exists, otherwise create it.
     *
     * @param  array<string, mixed>  $values  e.g. ['supportEmail' => 'help@example.com']
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
    }

    /**
     * Converts the stored text back to the type of the default (int stays int, bool stays bool ...).
     */
    private static function castBack(?string $stored, string|int|bool $default): string|int|bool
    {
        return match (true) {
            is_int($default) => (int) $stored,
            is_bool($default) => filter_var($stored, FILTER_VALIDATE_BOOLEAN),
            default => (string) $stored,
        };
    }
}
