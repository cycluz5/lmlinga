<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Plot confirmation request. Client may propose attributes; server assigns plot identity.
 * Client-supplied plot_id is intentionally not accepted as authority.
 */
class IssueSpotMappingHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $zone = $this->input('zone');
        if (is_numeric($zone)) {
            $zone = (string) (int) $zone;
        }

        $this->merge([
            'household_no' => trim((string) $this->input('household_no', '')),
            'house_head' => trim((string) $this->input('house_head', '')),
            'zone' => is_string($zone) ? trim($zone) : $zone,
            'client_marker_id' => trim((string) $this->input('client_marker_id', $this->input('plot_id', ''))),
            'consent' => filter_var($this->input('consent'), FILTER_VALIDATE_BOOLEAN) ? '1' : $this->input('consent'),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'household_no' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],
            'house_head' => ['required', 'string', 'max:255'],
            'household_type' => ['required', 'in:HHTS,Non-HHTS'],
            'zone' => ['required', 'string', 'max:32'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'consent' => ['required', 'accepted'],
            // Optional UI marker reference only — never used as plot identity.
            'client_marker_id' => ['nullable', 'string', 'max:128'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'household_no.required' => 'Household number is required.',
            'house_head.required' => 'Household head name is required.',
            'household_type.required' => 'Household type is required.',
            'zone.required' => 'Zone is required.',
            'lat.required' => 'Plot coordinates are required.',
            'lng.required' => 'Plot coordinates are required.',
            'consent.accepted' => 'Consent from the head of household is required before plotting.',
        ];
    }
}
