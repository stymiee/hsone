<?php

namespace App\Console\Commands;

use App\Events\ModelTransitioned;
use App\Events\ModelTransitioning;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class TestStateMachine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:state-machine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the State Machine trait with various examples';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('=== State Machine Demo ===');
        $this->newLine();

        // Show available states
        $this->info('Available States:');
        $states = Document::getAvailableStates();
        foreach ($states as $state) {
            $this->line("  - {$state}");
        }
        $this->newLine();

        // Example 1: Valid transitions
        $this->info('Example 1: Valid State Transitions');
        $this->line('Transition: draft → submitted → approved');
        $this->newLine();

        $document1 = Document::create([
            'title' => 'Test Document 1',
            'content' => 'This is a test document',
            'state' => 'draft',
        ]);

        $this->line("  Created document #{$document1->id} in state: {$document1->getCurrentState()}");
        $this->newLine();

        // Transition to submitted
        $this->line('  Transitioning to "submitted"...');
        $result = $document1->transitionTo('submitted');
        $document1->refresh();
        $this->line("    Result: " . ($result ? '✓ Success' : '✗ Failed'));
        $this->line("    Current state: {$document1->getCurrentState()}");
        $this->newLine();

        // Transition to approved
        $this->line('  Transitioning to "approved"...');
        $result = $document1->transitionTo('approved');
        $document1->refresh();
        $this->line("    Result: " . ($result ? '✓ Success' : '✗ Failed'));
        $this->line("    Current state: {$document1->getCurrentState()}");
        $this->newLine();

        // Example 2: Invalid transition
        $this->info('Example 2: Invalid Transition (Exception Handling)');
        $this->line('Attempting: approved → submitted (not allowed)');
        $this->newLine();

        try {
            $document1->transitionTo('submitted');
            $this->line('  ✗ Exception was not thrown (unexpected)');
        } catch (\InvalidArgumentException $e) {
            $this->line('  ✓ Exception caught as expected:');
            $this->line("    {$e->getMessage()}");
        }
        $this->newLine();

        // Example 3: Check allowed transitions
        $this->info('Example 3: Checking Allowed Transitions');
        $this->newLine();

        $document2 = Document::create([
            'title' => 'Test Document 2',
            'content' => 'Another test document',
            'state' => 'draft',
        ]);

        $this->line("  Document #{$document2->id} current state: {$document2->getCurrentState()}");
        $allowed = $document2->getAllowedTransitions();
        $this->line('  Allowed transitions: ' . (empty($allowed) ? 'none (final state)' : implode(', ', $allowed)));
        $this->newLine();

        // Check specific transitions
        $this->line('  Can transition to "submitted"? ' . ($document2->canTransitionTo('submitted') ? '✓ Yes' : '✗ No'));
        $this->line('  Can transition to "approved"? ' . ($document2->canTransitionTo('approved') ? '✓ Yes' : '✗ No'));
        $this->line('  Can transition to "rejected"? ' . ($document2->canTransitionTo('rejected') ? '✓ Yes' : '✗ No'));
        $this->newLine();

        // Example 4: Rejected path
        $this->info('Example 4: Rejection Path');
        $this->line('Transition: draft → submitted → rejected');
        $this->newLine();

        $document3 = Document::create([
            'title' => 'Test Document 3',
            'content' => 'Document to be rejected',
            'state' => 'draft',
        ]);

        $this->line("  Created document #{$document3->id} in state: {$document3->getCurrentState()}");

        $document3->transitionTo('submitted');
        $document3->refresh();
        $this->line("  After transition to 'submitted': {$document3->getCurrentState()}");

        $document3->transitionTo('rejected');
        $document3->refresh();
        $this->line("  After transition to 'rejected': {$document3->getCurrentState()}");

        $allowed = $document3->getAllowedTransitions();
        $this->line('  Allowed transitions from rejected: ' . (empty($allowed) ? 'none (final state)' : implode(', ', $allowed)));
        $this->newLine();

        // Example 5: Events
        $this->info('Example 5: Event Firing');
        $this->line('Creating a document and transitioning while listening to events');
        $this->newLine();

        Event::fake();

        $document4 = Document::create([
            'title' => 'Test Document 4',
            'content' => 'Document for event testing',
            'state' => 'draft',
        ]);

        $document4->transitionTo('submitted');

        // Check if events were fired
        Event::assertDispatched(ModelTransitioning::class, function ($event) use ($document4) {
            return $event->model->id === $document4->id
                && $event->oldState === 'draft'
                && $event->newState === 'submitted';
        });

        Event::assertDispatched(ModelTransitioned::class, function ($event) use ($document4) {
            return $event->model->id === $document4->id
                && $event->oldState === 'draft'
                && $event->newState === 'submitted';
        });

        $this->line('  ✓ ModelTransitioning event was fired');
        $this->line('  ✓ ModelTransitioned event was fired');
        $this->newLine();

        // Example 6: Transition without saving
        $this->info('Example 6: Transition Without Saving');
        $this->line('Transitioning without automatically saving to database');
        $this->newLine();

        $document5 = Document::create([
            'title' => 'Test Document 5',
            'content' => 'Document for no-save testing',
            'state' => 'draft',
        ]);

        $originalState = $document5->getCurrentState();
        $this->line("  Original state in DB: {$originalState}");

        // Transition without saving
        $document5->transitionTo('submitted', false);
        $this->line("  State on model after transition (no save): {$document5->getCurrentState()}");

        // Check database (should still be draft)
        $dbState = $document5->fresh()->state;
        $this->line("  State in database (after refresh): {$dbState}");

        // Now save manually
        $document5->save();
        $dbState = $document5->fresh()->state;
        $this->line("  State in database (after manual save): {$dbState}");
        $this->newLine();

        // Summary
        $this->info('Summary:');
        $this->line('  Total documents created: 5');
        $this->line('  States demonstrated: draft, submitted, approved, rejected');
        $this->line('  Events: ModelTransitioning and ModelTransitioned');
        $this->line('  Validation: Invalid transitions throw InvalidArgumentException');
        $this->newLine();

        $this->info('=== Demo Complete ===');
    }
}
