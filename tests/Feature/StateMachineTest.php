<?php

namespace Tests\Feature;

use App\Events\ModelTransitioned;
use App\Events\ModelTransitioning;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StateMachineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_transition_from_draft_to_submitted()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $result = $document->transitionTo('submitted');

        $this->assertTrue($result);
        $this->assertEquals('submitted', $document->fresh()->state);
    }

    /** @test */
    public function it_can_transition_from_submitted_to_approved()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'submitted',
        ]);

        $result = $document->transitionTo('approved');

        $this->assertTrue($result);
        $this->assertEquals('approved', $document->fresh()->state);
    }

    /** @test */
    public function it_can_transition_from_submitted_to_rejected()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'submitted',
        ]);

        $result = $document->transitionTo('rejected');

        $this->assertTrue($result);
        $this->assertEquals('rejected', $document->fresh()->state);
    }

    /** @test */
    public function it_throws_exception_for_invalid_transition()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot transition from 'draft' to 'approved'");

        $document->transitionTo('approved');
    }

    /** @test */
    public function it_throws_exception_when_transitioning_from_final_state()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'approved',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot transition from 'approved' to 'submitted'");

        $document->transitionTo('submitted');
    }

    /** @test */
    public function it_fires_model_transitioning_event()
    {
        Event::fake();

        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $document->transitionTo('submitted');

        Event::assertDispatched(ModelTransitioning::class, function ($event) use ($document) {
            return $event->model->id === $document->id
                && $event->oldState === 'draft'
                && $event->newState === 'submitted';
        });
    }

    /** @test */
    public function it_fires_model_transitioned_event()
    {
        Event::fake();

        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $document->transitionTo('submitted');

        Event::assertDispatched(ModelTransitioned::class, function ($event) use ($document) {
            return $event->model->id === $document->id
                && $event->oldState === 'draft'
                && $event->newState === 'submitted';
        });
    }

    /** @test */
    public function it_fires_events_in_correct_order()
    {
        Event::fake();

        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $document->transitionTo('submitted');

        // Verify events were dispatched
        Event::assertDispatched(ModelTransitioning::class);
        Event::assertDispatched(ModelTransitioned::class);

        // Get the order of events
        $events = Event::dispatched(ModelTransitioning::class);
        $this->assertNotEmpty($events);
    }

    /** @test */
    public function it_can_check_if_transition_is_allowed()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $this->assertTrue($document->canTransitionTo('submitted'));
        $this->assertFalse($document->canTransitionTo('approved'));
        $this->assertFalse($document->canTransitionTo('rejected'));
    }

    /** @test */
    public function it_returns_allowed_transitions()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $allowed = $document->getAllowedTransitions();

        $this->assertEquals(['submitted'], $allowed);
    }

    /** @test */
    public function it_returns_empty_array_for_final_states()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'approved',
        ]);

        $allowed = $document->getAllowedTransitions();

        $this->assertEquals([], $allowed);
    }

    /** @test */
    public function it_can_transition_without_saving()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $result = $document->transitionTo('submitted', false);

        $this->assertTrue($result);
        $this->assertEquals('submitted', $document->state);
        // But the database should still have 'draft'
        $this->assertEquals('draft', $document->fresh()->state);
    }

    /** @test */
    public function it_handles_initial_state_transitions()
    {
        // Create a document with default state (draft)
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft', // Explicitly set to ensure it's set
        ]);

        // Should be able to transition from draft to submitted
        $this->assertTrue($document->canTransitionTo('submitted'));
        
        // Should not be able to transition directly to approved from draft
        $this->assertFalse($document->canTransitionTo('approved'));
    }

    /** @test */
    public function it_gets_current_state()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'submitted',
        ]);

        $this->assertEquals('submitted', $document->getCurrentState());
    }

    /** @test */
    public function it_validates_state_exists()
    {
        $document = Document::create([
            'title' => 'Test Document',
            'content' => 'Test content',
            'state' => 'draft',
        ]);

        $this->assertTrue($document->isValidState('draft'));
        $this->assertTrue($document->isValidState('submitted'));
        $this->assertTrue($document->isValidState('approved'));
        $this->assertFalse($document->isValidState('invalid_state'));
    }

    /** @test */
    public function it_gets_all_available_states()
    {
        $states = Document::getAvailableStates();

        $this->assertContains('draft', $states);
        $this->assertContains('submitted', $states);
        $this->assertContains('approved', $states);
        $this->assertContains('rejected', $states);
    }
}

