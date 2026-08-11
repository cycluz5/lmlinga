<?php

namespace App\Http\Requests;

use App\Support\DemoCatalog;
use App\Support\DemoRiskAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRiskAssessmentSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $hh = DemoCatalog::normalizeHouseholdNo((string) $this->route('householdNo', ''));
        $mb = DemoCatalog::normalizeMemberId((string) $this->route('memberId', ''));
        $id = strtoupper(trim((string) $this->route('assessmentId', '')));
        $section = DemoRiskAssessment::normalizeSection((string) $this->route('section', ''));

        if ($section === null) {
            return false;
        }

        $household = DemoCatalog::findHousehold($hh);
        if (! $household) {
            return false;
        }

        $member = lml_demo_find_member($household, $mb);
        if (! $member) {
            return false;
        }

        // Server-side ownership: assessment must belong to this household+member.
        return DemoRiskAssessment::existsInCatalog($hh, $mb, $id);
    }

    protected function prepareForValidation(): void
    {
        $section = DemoRiskAssessment::normalizeSection((string) $this->route('section', ''));

        $merge = [];

        if (in_array($section, [
            DemoRiskAssessment::SECTION_RED_FLAGS,
            DemoRiskAssessment::SECTION_PAST_MEDICAL,
            DemoRiskAssessment::SECTION_FAMILY_HISTORY,
        ], true)) {
            $group = match ($section) {
                DemoRiskAssessment::SECTION_RED_FLAGS => 'red_flags',
                DemoRiskAssessment::SECTION_PAST_MEDICAL => 'past_medical',
                default => 'family_history',
            };
            $raw = $this->input($group, []);
            $list = is_array($raw) ? $raw : [];
            $merge[$group] = DemoRiskAssessment::applyNoneExclusive(
                array_map(static fn (mixed $v): string => trim((string) $v), $list)
            );
        }

        if ($section === DemoRiskAssessment::SECTION_LIFESTYLE) {
            $dietary = $this->input('dietary', []);
            $merge['tobacco'] = trim((string) $this->input('tobacco', ''));
            $merge['alcohol'] = trim((string) $this->input('alcohol', ''));
            $merge['physical_activity'] = trim((string) $this->input('physical_activity', ''));
            $merge['dietary'] = is_array($dietary)
                ? array_values(array_filter(array_map(
                    static fn (mixed $v): string => trim((string) $v),
                    $dietary
                )))
                : [];
        }

        if ($section === DemoRiskAssessment::SECTION_PHYSICAL) {
            $merge['visual_no_screening'] = $this->boolean('visual_no_screening');
            $merge['visual_blurred'] = $this->boolean('visual_blurred');
            foreach ([
                'height_cm', 'weight_kg', 'bmi', 'waist_cm',
                'systolic', 'diastolic', 'bp_status', 'visual_blurred_note',
            ] as $field) {
                $merge[$field] = trim((string) $this->input($field, ''));
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $section = DemoRiskAssessment::normalizeSection((string) $this->route('section', ''));

        return match ($section) {
            DemoRiskAssessment::SECTION_RED_FLAGS => [
                'red_flags' => ['nullable', 'array'],
                'red_flags.*' => ['string', Rule::in(DemoRiskAssessment::allowedKeysForGroup('red_flags'))],
            ],
            DemoRiskAssessment::SECTION_PAST_MEDICAL => [
                'past_medical' => ['nullable', 'array'],
                'past_medical.*' => ['string', Rule::in(DemoRiskAssessment::allowedKeysForGroup('past_medical'))],
            ],
            DemoRiskAssessment::SECTION_FAMILY_HISTORY => [
                'family_history' => ['nullable', 'array'],
                'family_history.*' => ['string', Rule::in(DemoRiskAssessment::allowedKeysForGroup('family_history'))],
            ],
            DemoRiskAssessment::SECTION_LIFESTYLE => [
                'tobacco' => ['nullable', 'string', Rule::in(array_merge([''], DemoRiskAssessment::allowedKeysForGroup('tobacco')))],
                'alcohol' => ['nullable', 'string', Rule::in(array_merge([''], DemoRiskAssessment::allowedKeysForGroup('alcohol')))],
                'physical_activity' => ['nullable', 'string', Rule::in(array_merge([''], DemoRiskAssessment::allowedKeysForGroup('physical_activity')))],
                'dietary' => ['nullable', 'array'],
                'dietary.*' => ['string', Rule::in(DemoRiskAssessment::allowedKeysForGroup('dietary'))],
            ],
            DemoRiskAssessment::SECTION_PHYSICAL => [
                'height_cm' => ['nullable', 'string', 'max:32'],
                'weight_kg' => ['nullable', 'string', 'max:32'],
                'bmi' => ['nullable', 'string', 'max:32'],
                'waist_cm' => ['nullable', 'string', 'max:32'],
                'systolic' => ['nullable', 'string', 'max:32'],
                'diastolic' => ['nullable', 'string', 'max:32'],
                'bp_status' => ['nullable', 'string', 'max:64'],
                'visual_no_screening' => ['sometimes', 'boolean'],
                'visual_blurred' => ['sometimes', 'boolean'],
                'visual_blurred_note' => ['nullable', 'string', 'max:255'],
            ],
            default => [],
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $section = DemoRiskAssessment::normalizeSection((string) $this->route('section', ''));
            if ($section === null) {
                $validator->errors()->add('section', 'Unknown risk assessment section.');
            }

            foreach (['red_flags', 'past_medical', 'family_history'] as $group) {
                $selected = $this->input($group);
                if (! is_array($selected)) {
                    continue;
                }
                if (in_array('none', $selected, true) && count($selected) > 1) {
                    $validator->errors()->add(
                        $group,
                        'None cannot be combined with other conditions.'
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function sectionPayload(): array
    {
        $section = DemoRiskAssessment::normalizeSection((string) $this->route('section', ''));
        $validated = $this->validated();

        return match ($section) {
            DemoRiskAssessment::SECTION_RED_FLAGS => [
                'red_flags' => $validated['red_flags'] ?? [],
            ],
            DemoRiskAssessment::SECTION_PAST_MEDICAL => [
                'past_medical' => $validated['past_medical'] ?? [],
            ],
            DemoRiskAssessment::SECTION_FAMILY_HISTORY => [
                'family_history' => $validated['family_history'] ?? [],
            ],
            DemoRiskAssessment::SECTION_LIFESTYLE => [
                'tobacco' => $validated['tobacco'] ?? '',
                'alcohol' => $validated['alcohol'] ?? '',
                'dietary' => $validated['dietary'] ?? [],
                'physical_activity' => $validated['physical_activity'] ?? '',
            ],
            DemoRiskAssessment::SECTION_PHYSICAL => [
                'height_cm' => $validated['height_cm'] ?? '',
                'weight_kg' => $validated['weight_kg'] ?? '',
                'bmi' => $validated['bmi'] ?? '',
                'waist_cm' => $validated['waist_cm'] ?? '',
                'systolic' => $validated['systolic'] ?? '',
                'diastolic' => $validated['diastolic'] ?? '',
                'bp_status' => $validated['bp_status'] ?? '',
                'visual_no_screening' => (bool) ($validated['visual_no_screening'] ?? false),
                'visual_blurred' => (bool) ($validated['visual_blurred'] ?? false),
                'visual_blurred_note' => $validated['visual_blurred_note'] ?? '',
            ],
            default => [],
        };
    }
}
