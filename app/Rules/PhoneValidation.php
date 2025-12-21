<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class PhoneValidation implements ValidationRule
{
    /**
     * Supported country codes with their expected digit count.
     */
    private const COUNTRY_CODES = [
        '225' => 10, // Côte d'Ivoire
        '226' => 8,  // Burkina Faso
        '229' => 8,  // Bénin
        '228' => 8,  // Togo
        '221' => 9,  // Sénégal
        '223' => 8,  // Mali
        '227' => 8,  // Niger
        '33' => 9,   // France
    ];

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Le numéro de téléphone doit être une chaîne de caractères.');

            return;
        }

        // Remove all spaces from the phone number
        $cleaned = str_replace(' ', '', $value);

        // Check if it starts with +
        if (! str_starts_with($cleaned, '+')) {
            $fail('Le numéro de téléphone doit commencer par un indicatif pays (ex: +225, +226, +33).');

            return;
        }

        // Extract country code and number
        $matched = false;
        foreach (self::COUNTRY_CODES as $code => $digitCount) {
            if (str_starts_with($cleaned, '+'.$code)) {
                $number = substr($cleaned, strlen('+'.$code));

                // Check if the remaining part contains only digits
                if (! ctype_digit($number)) {
                    $fail('Le numéro de téléphone ne doit contenir que des chiffres après l\'indicatif pays.');

                    return;
                }

                // Check if digit count matches expected count for this country
                if (strlen($number) !== $digitCount) {
                    $fail("Le numéro pour l'indicatif +{$code} doit contenir exactement {$digitCount} chiffres.");

                    return;
                }

                $matched = true;
                break;
            }
        }

        if (! $matched) {
            $supportedCodes = implode(', ', array_map(fn ($c) => '+'.$c, array_keys(self::COUNTRY_CODES)));
            $fail("L'indicatif pays n'est pas supporté. Indicatifs acceptés: {$supportedCodes}.");
        }
    }

    /**
     * Get the validation error message.
     */
    public static function getRegexPattern(): string
    {
        // Build a regex pattern that matches all supported formats
        $patterns = [];
        foreach (self::COUNTRY_CODES as $code => $digitCount) {
            $digitPattern = str_repeat('\d', $digitCount);
            $patterns[] = '\+'.$code.'\s?'.chunk_split($digitPattern, 2, '\s?');
        }

        return '/^('.implode('|', $patterns).')$/';
    }
}
