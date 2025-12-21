<?php

use App\Rules\PhoneValidation;

it('accepts valid phone numbers from supported countries', function (string $phone) {
    $rule = new PhoneValidation;
    $failed = false;
    $errorMessage = '';

    $rule->validate('phone', $phone, function ($message) use (&$failed, &$errorMessage) {
        $failed = true;
        $errorMessage = $message;
    });

    expect($failed)->toBeFalse("Expected {$phone} to be valid but got error: {$errorMessage}");
})->with([
    // Côte d'Ivoire (+225) - 10 digits
    '+2250123456789',
    '+225 01 23 45 67 89',
    '+22501 23 45 67 89',

    // Burkina Faso (+226) - 8 digits
    '+22670123456',
    '+226 70 12 34 56',
    '+22670 12 34 56',

    // Bénin (+229) - 8 digits
    '+22990123456',
    '+229 90 12 34 56',

    // Togo (+228) - 8 digits
    '+22890123456',
    '+228 90 12 34 56',

    // Sénégal (+221) - 9 digits
    '+221701234567',
    '+221 70 123 45 67',

    // Mali (+223) - 8 digits
    '+22370123456',
    '+223 70 12 34 56',

    // Niger (+227) - 8 digits
    '+22790123456',
    '+227 90 12 34 56',

    // France (+33) - 9 digits
    '+33612345678',
    '+33 6 12 34 56 78',
]);

it('rejects phone numbers without country code', function (string $phone) {
    $rule = new PhoneValidation;
    $failed = false;

    $rule->validate('phone', $phone, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue("Expected {$phone} to be invalid");
})->with([
    '70123456',
    '0701234567',
    '226 70 12 34 56',
]);

it('rejects phone numbers with unsupported country codes', function (string $phone) {
    $rule = new PhoneValidation;
    $failed = false;

    $rule->validate('phone', $phone, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue("Expected {$phone} to be invalid");
})->with([
    '+1234567890',      // USA
    '+447123456789',    // UK
    '+86123456789',     // China
]);

it('rejects phone numbers with incorrect digit count', function (string $phone) {
    $rule = new PhoneValidation;
    $failed = false;

    $rule->validate('phone', $phone, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue("Expected {$phone} to be invalid");
})->with([
    '+2267012345',      // Burkina - too few digits
    '+226701234567',    // Burkina - too many digits
    '+225012345678',    // Côte d'Ivoire - too few digits
    '+33612345',        // France - too few digits
]);

it('rejects phone numbers with non-digit characters', function (string $phone) {
    $rule = new PhoneValidation;
    $failed = false;

    $rule->validate('phone', $phone, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue("Expected {$phone} to be invalid");
})->with([
    '+226-70-12-34-56',
    '+226(70)123456',
    '+226 70.12.34.56',
    '+226 70a12345',
]);
