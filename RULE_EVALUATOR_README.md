# Rule Evaluator System

A dynamic rule evaluation system that checks if a user can perform a given action based on JSON rules stored in the database.

## Overview

The `RuleEvaluator` class evaluates user permissions based on flexible JSON rule sets. It supports multiple operators and can check various user attributes using PHP reflection and Laravel's helper functions.

## Components

### 1. RuleEvaluator Class
Located at: `app/Services/RuleEvaluator.php`

The main class that evaluates rules against a User model.

### 2. Rule Model
Located at: `app/Models/Rule.php`

Eloquent model for storing rule sets in the database.

### 3. Database Tables
- `rules` - Stores rule sets with action names and JSON rules
- `users` - Extended with a `role` field for rule evaluation

## Usage

### Basic Example

```php
use App\Models\User;
use App\Services\RuleEvaluator;

$evaluator = new RuleEvaluator();
$user = User::find(1);

$ruleSet = [
    'action' => 'submit_form',
    'rules' => [
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'staff',
        ],
        [
            'field' => 'email_verified_at',
            'operator' => '!=',
            'value' => null,
        ],
    ],
];

$canPerform = $evaluator->evaluate($user, $ruleSet);
// Returns true if user role is 'staff' AND email is verified
```

### Using JSON String

```php
$jsonRuleSet = json_encode([
    'action' => 'submit_form',
    'rules' => [
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'staff',
        ],
    ],
]);

$canPerform = $evaluator->evaluate($user, $jsonRuleSet);
```

### Storing Rules in Database

```php
use App\Models\Rule;

$rule = Rule::create([
    'action' => 'submit_form',
    'rules' => [
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'staff',
        ],
        [
            'field' => 'email_verified_at',
            'operator' => '!=',
            'value' => null,
        ],
    ],
]);

// Later, retrieve and evaluate
$rule = Rule::where('action', 'submit_form')->first();
$canPerform = $evaluator->evaluate($user, $rule->rules);
```

## Supported Operators

### 1. `==` (Equals)
Checks if the field value equals the expected value.

```php
[
    'field' => 'role',
    'operator' => '==',
    'value' => 'staff',
]
```

### 2. `!=` (Not Equals)
Checks if the field value does not equal the expected value.

```php
[
    'field' => 'email_verified_at',
    'operator' => '!=',
    'value' => null,
]
```

### 3. `in` (In Array)
Checks if the field value is in the provided array.

```php
[
    'field' => 'role',
    'operator' => 'in',
    'value' => ['admin', 'staff', 'manager'],
]
```

### 4. `not_in` (Not In Array)
Checks if the field value is not in the provided array.

```php
[
    'field' => 'role',
    'operator' => 'not_in',
    'value' => ['banned', 'suspended'],
]
```

### 5. `>` (Greater Than)
Checks if the field value is greater than the expected value (numeric).

```php
[
    'field' => 'id',
    'operator' => '>',
    'value' => 100,
]
```

### 6. `<` (Less Than)
Checks if the field value is less than the expected value (numeric).

```php
[
    'field' => 'id',
    'operator' => '<',
    'value' => 1000,
]
```

### 7. `contains` (Contains)
Checks if the field value contains the expected substring (for strings) or contains the value (for arrays).

```php
// For strings
[
    'field' => 'name',
    'operator' => 'contains',
    'value' => 'John',
]

// For arrays (checks if array contains the value)
[
    'field' => 'permissions',
    'operator' => 'contains',
    'value' => 'edit',
]
```

## Rule Set Structure

A rule set must have the following structure:

```json
{
    "action": "action_name",
    "rules": [
        {
            "field": "field_name",
            "operator": "operator",
            "value": "expected_value"
        }
    ]
}
```

### Multiple Rules (AND Logic)

All rules in the `rules` array must pass for the evaluation to return `true`. This implements AND logic.

```php
$ruleSet = [
    'action' => 'submit_form',
    'rules' => [
        ['field' => 'role', 'operator' => '==', 'value' => 'staff'],
        ['field' => 'email_verified_at', 'operator' => '!=', 'value' => null],
        ['field' => 'id', 'operator' => '>', 'value' => 0],
    ],
];
// All three rules must pass
```

## Field Access

The `RuleEvaluator` uses Laravel's `data_get()` helper and PHP reflection to access user model properties:

- **Public properties**: Accessed directly
- **Protected/Private properties**: Accessed via reflection
- **Relationships**: Can be accessed using dot notation (e.g., `profile.status`)
- **Accessors**: Automatically resolved by Laravel

## Testing

Run the test suite:

```bash
php artisan test --filter RuleEvaluatorTest
```

All 10 tests pass, covering:
- All operators (==, !=, in, not_in, >, <, contains)
- Multiple rules with AND logic
- JSON string input
- Invalid rule set handling

## Implementation Details

- Uses PHP Reflection API to access protected/private properties
- Uses Laravel's `data_get()` helper for flexible field access
- Supports both array and JSON string rule sets
- Implements strict type checking where appropriate
- Handles null values correctly
- Returns `false` for invalid rule sets or missing fields

