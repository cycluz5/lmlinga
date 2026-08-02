<?php

namespace App\Http\Requests;

use App\Support\DemoHouseholdWaterSupply;
use App\Support\DemoSpotMappingHandoff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHouseholdWaterSupplyStep2Request extends FormRequest
{
    /**
     * Normalized household_no from the request body (before route overwrite).
     */
    private string $submittedHouseholdNo = '';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Capture body value before the route parameter becomes authoritative.
        $this->submittedHouseholdNo = DemoHouseholdWaterSupply::normalizeHouseholdNo(
            (string) $this->input('household_no', '')
        );

        $routeHouseholdNo = DemoHouseholdWaterSupply::normalizeHouseholdNo(
            (string) $this->route('householdNo', '')
        );

        $this->merge([
            'household_no' => $routeHouseholdNo,
            'microbiological_test_date' => $this->blankToNull($this->input('microbiological_test_date')),
            'microbiological_result' => $this->blankToNull($this->input('microbiological_result')),
            'physicochemical_test_date' => $this->blankToNull($this->input('physicochemical_test_date')),
            'physicochemical_result' => $this->blankToNull($this->input('physicochemical_result')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'household_no' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],
            'microbiological_test_date' => ['nullable', 'date'],
            'microbiological_result' => ['nullable', 'in:passed,failed'],
            'physicochemical_test_date' => ['nullable', 'date'],
            'physicochemical_result' => ['nullable', 'in:passed,failed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'household_no.required' => 'A linked household is required.',
            'household_no.regex' => 'The household number format is invalid.',
            'microbiological_test_date.date' => 'Please select a valid microbiological test date.',
            'microbiological_result.in' => 'Please select the microbiological test result.',
            'physicochemical_test_date.date' => 'Please select a valid physico-chemical test date.',
            'physicochemical_result.in' => 'Please select the physico-chemical test result.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeHouseholdNo = (string) $this->input('household_no', '');

            if ($this->submittedHouseholdNo !== ''
                && $this->submittedHouseholdNo !== $routeHouseholdNo) {
                $validator->errors()->add(
                    'household_no',
                    'The household number does not match this form URL.'
                );

                return;
            }

            $microDate = $this->input('microbiological_test_date');
            $microResult = $this->input('microbiological_result');
            $physicoDate = $this->input('physicochemical_test_date');
            $physicoResult = $this->input('physicochemical_result');

            $hasMicroDate = is_string($microDate) && $microDate !== '';
            $hasMicroResult = is_string($microResult) && $microResult !== '';
            $hasPhysicoDate = is_string($physicoDate) && $physicoDate !== '';
            $hasPhysicoResult = is_string($physicoResult) && $physicoResult !== '';

            if ($hasMicroDate && ! $hasMicroResult) {
                $validator->errors()->add(
                    'microbiological_result',
                    'Please select the microbiological test result.'
                );
            }

            if ($hasMicroResult && ! $hasMicroDate) {
                $validator->errors()->add(
                    'microbiological_test_date',
                    'Please select the microbiological test date.'
                );
            }

            if ($hasPhysicoDate && ! $hasPhysicoResult) {
                $validator->errors()->add(
                    'physicochemical_result',
                    'Please select the physico-chemical test result.'
                );
            }

            if ($hasPhysicoResult && ! $hasPhysicoDate) {
                $validator->errors()->add(
                    'physicochemical_test_date',
                    'Please select the physico-chemical test date.'
                );
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! DemoHouseholdWaterSupply::hasCompletedStep1($routeHouseholdNo)
                || ! DemoHouseholdWaterSupply::isRecognized($routeHouseholdNo)) {
                $validator->errors()->add(
                    'household_no',
                    DemoSpotMappingHandoff::INVALID_MESSAGE
                );
            }
        });
    }

    private function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
