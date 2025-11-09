# Filter Builder System

A flexible filter class that accepts JSON filters and applies them across multiple relationships using dot notation.

## Overview

The `FilterBuilder` class allows you to filter Eloquent queries using a simple JSON structure that supports:
- Direct model field filtering
- Relationship filtering using dot notation
- Automatic fallback to `where` when relationships don't exist
- Support for `whereHas` and `orWhereHas`
- Handling of model prefix (e.g., "appointment.status" → "status")

## Components

### 1. FilterBuilder Class
Located at: `app/Services/FilterBuilder.php`

The main class that applies filters to Eloquent queries.

### 2. Models
- `Appointment` - Main model with relationships to Patient and Location
- `Patient` - Has many appointments
- `Location` - Has many appointments

## Usage

### Basic Example

```php
use App\Models\Appointment;
use App\Services\FilterBuilder;

$filters = [
    'patient.name' => 'John',
    'status' => 'confirmed',
    'location.city' => 'Dallas',
];

$query = FilterBuilder::applyFilters(Appointment::query(), $filters);
$appointments = $query->get();
```

### Using JSON String

```php
$jsonFilters = json_encode([
    'patient.name' => 'John',
    'status' => 'confirmed',
]);

$query = FilterBuilder::applyFilters(Appointment::query(), $jsonFilters);
$appointments = $query->get();
```

### Example from Requirements

```php
$filters = [
    'patient.name' => 'John',
    'appointment.status' => 'confirmed',  // "appointment." prefix is automatically stripped
    'location.city' => 'Dallas',
];

$query = FilterBuilder::applyFilters(Appointment::query(), $filters);
$appointments = $query->get();
```

### Using OR Conditions

```php
$query = Appointment::query()->where('status', 'pending');

$filters = [
    'patient.name' => 'John',
];

$builder = new FilterBuilder($query, $filters);
$builder->useOrWhere(true);
$query = $builder->apply();
$appointments = $query->get();
```

## How It Works

### Dot Notation

The FilterBuilder uses dot notation to traverse relationships:

- `patient.name` - Filters on the `name` field of the related `patient`
- `location.city` - Filters on the `city` field of the related `location`
- `status` - Filters directly on the `status` field of the current model

### SQL Generation

The FilterBuilder generates appropriate SQL based on the filter type:

1. **Direct Fields**: Uses `WHERE` clause
   ```sql
   SELECT * FROM appointments WHERE status = ?
   ```

2. **Relationship Fields**: Uses `WHERE EXISTS` with subquery
   ```sql
   SELECT * FROM appointments 
   WHERE EXISTS (
       SELECT * FROM patients 
       WHERE appointments.patient_id = patients.id 
       AND name = ?
   )
   ```

3. **Multiple Relationships**: Uses multiple `WHERE EXISTS` clauses
   ```sql
   SELECT * FROM appointments 
   WHERE status = ?
   AND EXISTS (SELECT * FROM patients WHERE ...)
   AND EXISTS (SELECT * FROM locations WHERE ...)
   ```

### Model Prefix Handling

When filtering on a model, if the field starts with the model name (e.g., `appointment.status` when querying `Appointment`), the prefix is automatically stripped:

- `appointment.status` → `status` (direct field)
- `patient.name` → Uses `whereHas` on `patient` relationship

### Fallback Behavior

If a relationship doesn't exist on the model, the FilterBuilder falls back to applying the filter as a direct field:

```php
// If "nonexistent_relation" doesn't exist
$filters = [
    'nonexistent_relation.field' => 'value',
];
// Falls back to: WHERE nonexistent_relation.field = 'value'
```

## Testing

Run the test suite:

```bash
php artisan test --filter FilterBuilderTest
```

All 12 tests pass, covering:
- Direct model field filtering
- Relationship filtering with `whereHas`
- Multiple filters across relationships
- JSON string input
- Model prefix stripping
- Fallback to `where` when relationship doesn't exist
- `orWhereHas` support
- SQL structure verification

## Implementation Details

- Uses Laravel's Eloquent query builder
- Automatically detects relationships using `method_exists()`
- Supports nested relationships (e.g., `patient.profile.status`)
- Handles both array and JSON string filter inputs
- Strips model name prefix for cleaner filter syntax
- Generates efficient SQL with `EXISTS` subqueries

