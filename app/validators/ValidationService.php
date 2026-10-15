<?php
/**
 * ValidationService - Common validation and sanitization utilities
 */

declare(strict_types=1);

namespace App\Validators;

class ValidationService
{
    private array $errors = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public static function sanitizeString(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeEmail(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        return strtolower(trim(filter_var($email, FILTER_SANITIZE_EMAIL) ?: ''));
    }

    public function validateEmail(string $field, ?string $email, bool $required = true): ?string
    {
        $email = self::sanitizeEmail($email);
        if ($email === '') {
            if ($required) {
                $this->addError($field, 'Email address is required.');
            }
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Please enter a valid email address.');
            return null;
        }
        return $email;
    }

    public function validatePassword(string $field, ?string $password, int $minLength = 6): ?string
    {
        if ($password === null || strlen($password) < $minLength) {
            $this->addError($field, "Password must be at least {$minLength} characters long.");
            return null;
        }
        return $password;
    }

    public function validateRequired(string $field, ?string $value, string $label): ?string
    {
        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            $this->addError($field, "{$label} is required.");
            return null;
        }
        return $trimmed;
    }

    public function validateInArray(string $field, ?string $value, array $allowed, string $label): ?string
    {
        if ($value === null || !in_array($value, $allowed, true)) {
            $this->addError($field, "Invalid selection for {$label}. Allowed: " . implode(', ', $allowed));
            return null;
        }
        return $value;
    }

    public function validateRange(string $field, mixed $value, int $min, int $max, string $label): ?int
    {
        if ($value === null || !is_numeric($value)) {
            $this->addError($field, "{$label} must be a number.");
            return null;
        }
        $val = (int)$value;
        if ($val < $min || $val > $max) {
            $this->addError($field, "{$label} must be between {$min} and {$max}.");
            return null;
        }
        return $val;
    }
}
