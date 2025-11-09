# Laravel Take-Home Test

This repository contains a Laravel application with three implemented PHP/Laravel Coding Challenges,

## Overview

This project implements three distinct features:

1. **Rule Evaluator System** - Dynamic rule evaluation based on JSON rules stored in the database
2. **Filter Builder** - Flexible filtering across Eloquent relationships using dot notation
3. **State Machine Trait** - Lightweight state machine for Eloquent models with event support

## Requirements

- PHP 8.2 or higher
- Composer
- SQLite (included, no additional setup needed)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/stymiee/hsone.git
cd hsone
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

That's it! The application uses SQLite, so no database configuration is needed.

## Running the Demonstrations

Each task has a dedicated artisan command that demonstrates its functionality with real examples.

### Task 1: Rule Evaluator

Run the Rule Evaluator demonstration:
```bash
php artisan test:rule-evaluator
```

This command will:
- Create test users with different roles and verification statuses
- Demonstrate rule evaluation with various operators (==, !=, in, contains)
- Show the exact example from the requirements
- Display evaluation results for each scenario

**Example Output:**
- Shows which users can perform actions based on JSON rules
- Demonstrates operators: ==, !=, in, not_in, >, <, contains
- Tests both array and JSON string rule sets

### Task 2: Filter Builder

Run the Filter Builder demonstration:
```bash
php artisan test:filter-builder
```

This command will:
- Create test data (patients, locations, appointments)
- Demonstrate filtering across relationships using dot notation
- Show the exact example from requirements (patient.name, appointment.status, location.city)
- Display the generated SQL queries

**Example Output:**
- Shows filtering on direct model fields
- Demonstrates relationship filtering with dot notation
- Displays the actual SQL generated (with WHERE EXISTS subqueries)
- Tests JSON string input

### Task 3: State Machine

Run the State Machine demonstration:
```bash
php artisan test:state-machine
```

This command will:
- Create test documents and demonstrate state transitions
- Show valid and invalid transitions
- Demonstrate event firing (ModelTransitioning and ModelTransitioned)
- Test transition validation

**Example Output:**
- Shows state transitions: draft → submitted → approved/rejected
- Demonstrates exception handling for invalid transitions
- Shows event dispatching
- Tests transition without saving functionality

## Project Structure

```
app/
├── Console/Commands/
│   ├── TestRuleEvaluator.php      # Task 1 demo command
│   ├── TestFilterBuilder.php      # Task 2 demo command
│   └── TestStateMachine.php       # Task 3 demo command
├── Events/
│   ├── ModelTransitioning.php    # State machine event (before)
│   └── ModelTransitioned.php      # State machine event (after)
├── Models/
│   ├── Appointment.php            # Task 2 model
│   ├── Document.php               # Task 3 model
│   ├── Location.php               # Task 2 model
│   ├── Patient.php                # Task 2 model
│   ├── Rule.php                   # Task 1 model
│   └── User.php                   # Task 1 model
├── Services/
│   ├── FilterBuilder.php          # Task 2 service
│   └── RuleEvaluator.php          # Task 1 service
└── Traits/
    └── StateMachine.php           # Task 3 trait

tests/
├── Feature/
│   ├── FilterBuilderTest.php      # Task 2 tests
│   ├── RuleEvaluatorTest.php      # Task 1 tests
│   └── StateMachineTest.php       # Task 3 tests
```

## Running Tests

Run all tests:
```bash
php artisan test
```

Run tests for a specific task:
```bash
# Task 1
php artisan test --filter RuleEvaluatorTest

# Task 2
php artisan test --filter FilterBuilderTest

# Task 3
php artisan test --filter StateMachineTest
```

## Task Details

### Task 1: Rule Evaluator
- **File**: `app/Services/RuleEvaluator.php`
- **Purpose**: Evaluates if a user can perform an action based on JSON rules
- **Operators**: ==, !=, in, not_in, >, <, contains
- **Features**: Uses PHP Reflection and Laravel helpers for field access

### Task 2: Filter Builder
- **File**: `app/Services/FilterBuilder.php`
- **Purpose**: Applies JSON filters across Eloquent relationships
- **Features**: Dot notation support, automatic whereHas/orWhereHas, SQL generation

### Task 3: State Machine
- **File**: `app/Traits/StateMachine.php`
- **Purpose**: Manages state transitions with validation and events
- **Features**: Transition validation, event firing, helper methods

## Documentation

Each task has detailed documentation:
- `RULE_EVALUATOR_README.md` - Task 1 documentation
- `FILTER_BUILDER_README.md` - Task 2 documentation
- `STATE_MACHINE_README.md` - Task 3 documentation

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
