<?php

namespace App\Services;

use App\Models\User;
use JsonException;
use ReflectionClass;
use ReflectionProperty;

class RuleEvaluator
{
    /**
     * Evaluate if a user can perform an action based on JSON rules.
     *
     * @param User $user The user model to evaluate
     * @param array|string $ruleSet The rule set (array or JSON string)
     * @return bool True if all rules pass, false otherwise
     * @throws JsonException
     */
    public function evaluate(User $user, array|string $ruleSet): bool
    {
        // If ruleSet is a JSON string, decode it
        if (is_string($ruleSet)) {
            $ruleSet = json_decode($ruleSet, true, 512, JSON_THROW_ON_ERROR);
        }

        // Validate rule set structure
        if (!isset($ruleSet['rules']) || !is_array($ruleSet['rules'])) {
            return false;
        }

        // Evaluate all rules - all must pass (AND logic)
        foreach ($ruleSet['rules'] as $rule) {
            if (!$this->evaluateRule($user, $rule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single rule against the user.
     *
     * @param User $user The user model
     * @param array $rule The rule to evaluate
     * @return bool True if rule passes, false otherwise
     */
    protected function evaluateRule(User $user, array $rule): bool
    {
        // Validate rule structure
        if (!isset($rule['field'], $rule['operator']) || !array_key_exists('value', $rule)) {
            return false;
        }

        $field = $rule['field'];
        $operator = $rule['operator'];
        $expectedValue = $rule['value'];

        // Get the actual value from the user model
        $actualValue = $this->getFieldValue($user, $field);

        // Evaluate based on operator
        return match ($operator) {
            '==' => $this->equals($actualValue, $expectedValue),
            '!=' => $this->notEquals($actualValue, $expectedValue),
            'in' => $this->in($actualValue, $expectedValue),
            'not_in' => $this->notIn($actualValue, $expectedValue),
            '>' => $this->greaterThan($actualValue, $expectedValue),
            '<' => $this->lessThan($actualValue, $expectedValue),
            'contains' => $this->contains($actualValue, $expectedValue),
            default => false,
        };
    }

    /**
     * Get the value of a field from the user model using reflection or Laravel helpers.
     *
     * @param User $user The user model
     * @param string $field The field name
     * @return mixed The field value
     */
    protected function getFieldValue(User $user, string $field): mixed
    {
        // Try Laravel's data_get helper first (supports dot notation and relationships)
        $value = data_get($user, $field);

        // If data_get returns null, try reflection to access protected/private properties
        if ($value === null && !$user->offsetExists($field)) {
            $reflection = new ReflectionClass($user);

            // Try to get as property
            if ($reflection->hasProperty($field)) {
                $property = $reflection->getProperty($field);
                $property->setAccessible(true);
                $value = $property->getValue($user);
            }
        }

        return $value;
    }

    /**
     * Check if two values are equal (==).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value
     * @return bool
     */
    protected function equals(mixed $actual, mixed $expected): bool
    {
        // Handle null comparison
        if ($expected === null) {
            return $actual === null;
        }

        return $actual === $expected;
    }

    /**
     * Check if two values are not equal (!=).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value
     * @return bool
     */
    protected function notEquals(mixed $actual, mixed $expected): bool
    {
        // Handle null comparison
        if ($expected === null) {
            return $actual !== null;
        }

        return $actual !== $expected;
    }

    /**
     * Check if value is in an array (in).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected array
     * @return bool
     */
    protected function in(mixed $actual, mixed $expected): bool
    {
        if (!is_array($expected)) {
            return false;
        }

        return in_array($actual, $expected, true);
    }

    /**
     * Check if value is not in an array (not_in).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected array
     * @return bool
     */
    protected function notIn(mixed $actual, mixed $expected): bool
    {
        if (!is_array($expected)) {
            return false;
        }

        return !in_array($actual, $expected, true);
    }

    /**
     * Check if value is greater than (>).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value
     * @return bool
     */
    protected function greaterThan(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        return (float) $actual > (float) $expected;
    }

    /**
     * Check if value is less than (<).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value
     * @return bool
     */
    protected function lessThan(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        return (float) $actual < (float) $expected;
    }

    /**
     * Check if value contains a substring or array contains an item (contains).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value to search for
     * @return bool
     */
    protected function contains(mixed $actual, mixed $expected): bool
    {
        // If actual is an array, check if it contains the expected value
        if (is_array($actual)) {
            return in_array($expected, $actual, true);
        }

        // If actual is a string, check if it contains the expected substring
        if (is_string($actual) && (is_string($expected) || is_numeric($expected))) {
            return str_contains($actual, (string) $expected);
        }

        return false;
    }
}

