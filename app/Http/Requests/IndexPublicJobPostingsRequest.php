<?php

namespace App\Http\Requests;

use App\Enums\JobWorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPublicJobPostingsRequest extends FormRequest
{
    private const SEARCH_KEYS = [
        'job_title',
        'job_type',
        'disability_type',
        'location',
        'company_industry',
        'category',
        'skill',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Ensure GET query parameters are included when validating (public job filters).
     *
     * `search` may be:
     * - Nested query params: search[job_title]=…&search[job_type]=…
     * - Dot-style params (Postman): search.job_title=… , search.location=… (merged into `search`)
     * - JSON object string: search={"job_title":"…","location":"…"}
     * - Plain string: search=engineer → keyword across title, location, industry + disability JSON text
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge($this->request->all(), $this->query->all());
    }

    protected function prepareForValidation(): void
    {
        $fromDots = [];
        foreach (self::SEARCH_KEYS as $key) {
            $flat = 'search.'.$key;
            if ($this->has($flat) && $this->filled($flat)) {
                $fromDots[$key] = $this->input($flat);
            }
        }

        $raw = $this->input('search');

        if (($raw === null || $raw === '') && $fromDots === []) {
            return;
        }

        if ($raw === null || $raw === '') {
            $this->finalizeSearchBag($fromDots);

            return;
        }

        if (is_array($raw)) {
            $this->finalizeSearchBag(array_merge($raw, $fromDots));

            return;
        }

        if (! is_string($raw)) {
            $this->merge(['search' => null]);

            return;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            $this->finalizeSearchBag($fromDots);

            return;
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->finalizeSearchBag(array_merge($decoded, $fromDots));

            return;
        }

        $this->finalizeSearchBag(array_merge(['_general' => $trimmed], $fromDots));
    }

    /**
     * @param  array<string, mixed>  $bag
     */
    private function finalizeSearchBag(array $bag): void
    {
        $clean = $this->cleanSearchBag($bag);
        $this->merge(['search' => $clean === [] ? null : $clean]);
    }

    /**
     * @param  array<string, mixed>  $search
     * @return array<string, mixed>
     */
    private function cleanSearchBag(array $search): array
    {
        $clean = [];
        foreach (self::SEARCH_KEYS as $key) {
            if (! array_key_exists($key, $search)) {
                continue;
            }
            $val = $search[$key];
            if ($val === '' || $val === null) {
                continue;
            }
            $clean[$key] = $val;
        }

        if (isset($clean['job_type'])) {
            $jt = $clean['job_type'];
            if (is_string($jt)) {
                $resolved = self::canonicalJobTypeForSearch(trim($jt));
                if ($resolved !== null) {
                    $clean['job_type'] = $resolved;
                } else {
                    $clean['job_type'] = strtolower(trim($jt));
                }
            }
        }

        foreach (['job_title', 'disability_type', 'location', 'company_industry', 'category', 'skill'] as $sk) {
            if (isset($clean[$sk]) && is_string($clean[$sk])) {
                $t = trim($clean[$sk]);
                if ($t === '') {
                    unset($clean[$sk]);
                } else {
                    $clean[$sk] = $t;
                }
            }
        }

        if (isset($search['_general']) && is_string($search['_general'])) {
            $g = trim($search['_general']);
            if ($g !== '') {
                $clean['_general'] = $g;
            }
        }

        return $clean;
    }

    /**
     * Accept UI variants (e.g. "full time", "Full-Time") and map to stored enum values.
     */
    private static function canonicalJobTypeForSearch(string $input): ?string
    {
        if ($input === '') {
            return null;
        }

        $spaced = strtolower(str_replace(['-', '_'], ' ', trim($input)));
        $spaced = preg_replace('/\s+/', ' ', $spaced);

        $hit = match ($spaced) {
            'full time', 'fulltime' => JobWorkType::Fulltime,
            'freelance', 'freelancer' => JobWorkType::Freelancing,
            default => null,
        };

        if ($hit !== null) {
            return $hit->value;
        }

        $compact = str_replace(' ', '', $spaced);

        return JobWorkType::tryFrom($compact)?->value
            ?? JobWorkType::tryFrom($spaced)?->value;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'array'],
            'search._general' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search.job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search.job_type' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(array_map(static fn (JobWorkType $t): string => $t->value, JobWorkType::cases())),
            ],
            'search.disability_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search.location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search.company_industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'search.category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search.skill' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
