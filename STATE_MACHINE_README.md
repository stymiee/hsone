# State Machine Trait

A lightweight StateMachine trait for Eloquent models that manages state transitions with validation and event dispatching.

## Overview

The `StateMachine` trait provides a simple way to manage state transitions in Eloquent models. It validates transitions based on a defined state graph, fires events before and after transitions, and provides helper methods for checking allowed transitions.

## Components

### 1. StateMachine Trait
Located at: `app/Traits/StateMachine.php`

The main trait that provides state machine functionality.

### 2. Event Classes
- `ModelTransitioning` - Fired before a state transition
- `ModelTransitioned` - Fired after a state transition

### 3. Example Model
- `Document` - Example model demonstrating the StateMachine trait

## Usage

### Basic Setup

1. Add the trait to your model:

```php
use App\Traits\StateMachine;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use StateMachine;

    /**
     * Define the states and their allowed transitions.
     *
     * @var array<string, array<string>>
     */
    public static array $states = [
        'draft' => ['submitted'],
        'submitted' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'title',
        'content',
        'state',
    ];
}
```

2. Add a `state` column to your migration:

```php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content')->nullable();
    $table->string('state')->default('draft');
    $table->timestamps();
});
```

### Transitioning States

```php
$document = Document::create([
    'title' => 'My Document',
    'content' => 'Document content',
    'state' => 'draft',
]);

// Transition to submitted
$document->transitionTo('submitted');

// Transition to approved
$document->transitionTo('approved');
```

### Checking Allowed Transitions

```php
// Check if a transition is allowed
if ($document->canTransitionTo('approved')) {
    $document->transitionTo('approved');
}

// Get all allowed transitions from current state
$allowed = $document->getAllowedTransitions();
// Returns: ['submitted'] when state is 'draft'
```

### Handling Invalid Transitions

```php
try {
    $document->transitionTo('approved'); // Invalid: draft -> approved
} catch (\InvalidArgumentException $e) {
    // Handle invalid transition
    echo $e->getMessage();
    // "Cannot transition from 'draft' to 'approved'. Allowed transitions: submitted"
}
```

### Events

The trait fires two events during transitions:

1. **ModelTransitioning** - Fired before the transition
2. **ModelTransitioned** - Fired after the transition

Listen to these events in your `EventServiceProvider`:

```php
use App\Events\ModelTransitioning;
use App\Events\ModelTransitioned;

protected $listen = [
    ModelTransitioning::class => [
        // Your listeners
    ],
    ModelTransitioned::class => [
        // Your listeners
    ],
];
```

Or use event listeners:

```php
use App\Events\ModelTransitioned;

Event::listen(ModelTransitioned::class, function ($event) {
    $model = $event->model;
    $oldState = $event->oldState;
    $newState = $event->newState;
    
    // Log the transition, send notifications, etc.
});
```

### Transition Without Saving

You can transition without automatically saving:

```php
$document->transitionTo('submitted', false);
// State is updated on the model but not saved to database
// You can then save manually or make other changes first
$document->save();
```

### Custom State Column Name

If your state column has a different name, override the method:

```php
protected function getStateColumnName(): string
{
    return 'status'; // Instead of 'state'
}
```

### Getting Current State

```php
$currentState = $document->getCurrentState();
```

### Getting All Available States

```php
$allStates = Document::getAvailableStates();
// Returns: ['draft', 'submitted', 'approved', 'rejected']
```

### Validating States

```php
// Check if a state is valid (exists in the states array)
$isValid = $document->isValidState('draft'); // true
$isValid = $document->isValidState('invalid'); // false
```

## State Definition Format

The `$states` array defines the state graph:

```php
public static array $states = [
    'current_state' => ['allowed_state1', 'allowed_state2'],
    'another_state' => ['final_state'],
    'final_state' => [], // Empty array means no transitions allowed (final state)
];
```

- **Keys** represent the current state
- **Values** are arrays of allowed transitions from that state
- **Empty arrays** represent final states with no allowed transitions

## Example: Document Workflow

```php
// Initial state: draft
$document = Document::create(['title' => 'Test', 'state' => 'draft']);

// Valid transition: draft -> submitted
$document->transitionTo('submitted');

// Valid transition: submitted -> approved
$document->transitionTo('approved');

// Invalid transition: approved -> submitted (throws exception)
// $document->transitionTo('submitted'); // ❌ Exception!
```

## Testing

Run the test suite:

```bash
php artisan test --filter StateMachineTest
```

All 16 tests pass, covering:
- Valid state transitions
- Invalid transition handling
- Event dispatching
- Transition validation
- Helper methods
- Edge cases

## Implementation Details

- Uses static `$states` array for state definition
- Validates transitions before executing
- Fires `ModelTransitioning` event before transition
- Fires `ModelTransitioned` event after transition
- Supports optional saving after transition
- Provides helper methods for state management
- Throws `InvalidArgumentException` for invalid transitions
- Handles null/empty states as initial states

