<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * PlatformSettingsController  -  Super Admin > Settings page.
 *
 *   GET   /api/platform/settings   -> every setting (saved value, or the default if never saved)
 *   PATCH /api/platform/settings   -> save the settings that are sent
 *
 * Super Admin only (see routes/api.php). The storage logic lives in App\Models\PlatformSetting.
 */
class PlatformSettingsController extends Controller
{
    /** GET /api/platform/settings */
    public function show()
    {
        return response()->json(PlatformSetting::all_());
    }

    /**
     * PATCH /api/platform/settings  { supportEmail: "help@example.com", trialDays: 30 }
     * Returns ALL settings again, so the React form can refresh itself from one response.
     */
    public function update(Request $request)
    {
        $data = $request->validate($this->rules());

        PlatformSetting::put($data);
        ActivityLog::record('Platform settings updated');

        return response()->json(PlatformSetting::all_());
    }

    /**
     * One rule per setting in PlatformSetting::DEFAULTS. `sometimes` = only validate what was sent.
     * `present` is not needed: empty text is allowed for the optional contact fields.
     */
    private function rules(): array
    {
        return [
            'platformName' => ['sometimes', 'string', 'min:2', 'max:80'],
            'supportEmail' => ['sometimes', 'nullable', 'email', 'max:120'],
            'supportPhone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'defaultCurrency' => ['sometimes', Rule::in(['AFN', 'USD'])],
            // The default plan must be a plan that exists.
            'defaultPlanId' => ['sometimes', Rule::exists('plans', 'id')],
            'trialDays' => ['sometimes', 'integer', 'between:0,365'],
        ];
    }
}
