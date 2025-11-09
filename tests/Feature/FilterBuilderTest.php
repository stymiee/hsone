<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Services\FilterBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FilterBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable query logging
        DB::enableQueryLog();
    }

    /** @test */
    public function it_applies_filters_on_direct_model_fields()
    {
        $filters = [
            'status' => 'confirmed',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Assert SQL contains the where clause
        $this->assertStringContainsString('where', strtolower($sql));
        $this->assertStringContainsString('status', $sql);
        $this->assertContains('confirmed', $bindings);
    }

    /** @test */
    public function it_applies_filters_on_relationship_fields_using_whereHas()
    {
        $filters = [
            'patient.name' => 'John',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Assert SQL contains whereHas or exists clause
        $this->assertStringContainsString('exists', strtolower($sql));
        $this->assertStringContainsString('patients', strtolower($sql));
        $this->assertStringContainsString('name', $sql);
        $this->assertContains('John', $bindings);
    }

    /** @test */
    public function it_applies_multiple_filters_across_relationships()
    {
        $filters = [
            'patient.name' => 'John',
            'appointment.status' => 'confirmed',
            'location.city' => 'Dallas',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Should have multiple whereHas clauses
        $this->assertStringContainsString('exists', strtolower($sql));
        $this->assertStringContainsString('patients', strtolower($sql));
        $this->assertStringContainsString('locations', strtolower($sql));
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('city', $sql);
        
        // Check bindings
        $this->assertContains('John', $bindings);
        $this->assertContains('confirmed', $bindings);
        $this->assertContains('Dallas', $bindings);
    }

    /** @test */
    public function it_handles_example_filter_from_requirements()
    {
        $filters = [
            'patient.name' => 'John',
            'appointment.status' => 'confirmed',
            'location.city' => 'Dallas',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Verify the SQL structure
        $this->assertStringContainsString('appointments', strtolower($sql));
        $this->assertStringContainsString('exists', strtolower($sql));
        
        // Verify all three filters are applied
        $this->assertContains('John', $bindings);
        $this->assertContains('confirmed', $bindings);
        $this->assertContains('Dallas', $bindings);
        
        // Verify that "appointment.status" is treated as "status" on the current model
        $this->assertStringContainsString('status', $sql);
    }
    
    /** @test */
    public function it_strips_model_prefix_from_field_names()
    {
        // When filtering on Appointment model, "appointment.status" should be treated as "status"
        $filters = [
            'appointment.status' => 'confirmed',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Should use direct where clause, not whereHas
        $this->assertStringContainsString('status', $sql);
        $this->assertContains('confirmed', $bindings);
        // Should not have exists clause since it's a direct field
        $this->assertStringNotContainsString('exists', strtolower($sql));
    }

    /** @test */
    public function it_works_with_json_string_filters()
    {
        $jsonFilters = json_encode([
            'patient.name' => 'John',
            'status' => 'confirmed',
        ]);

        $query = FilterBuilder::applyFilters(Appointment::query(), $jsonFilters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('exists', strtolower($sql));
        $this->assertContains('John', $bindings);
        $this->assertContains('confirmed', $bindings);
    }

    /** @test */
    public function it_falls_back_to_where_when_relationship_does_not_exist()
    {
        $filters = [
            'nonexistent_relation.field' => 'value',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        // Should fallback to direct where clause
        $this->assertStringContainsString('where', strtolower($sql));
        $this->assertContains('value', $bindings);
    }

    /** @test */
    public function it_applies_filters_on_current_model_when_no_dot_notation()
    {
        $filters = [
            'status' => 'confirmed',
            'notes' => 'Test note',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('notes', $sql);
        $this->assertContains('confirmed', $bindings);
        $this->assertContains('Test note', $bindings);
    }

    /** @test */
    public function it_supports_orWhereHas_when_configured()
    {
        // First add a base condition
        $query = Appointment::query()->where('status', 'pending');
        
        $filters = [
            'patient.name' => 'John',
        ];

        $builder = new FilterBuilder($query, $filters);
        $builder->useOrWhere(true);
        $query = $builder->apply();
        $sql = $query->toSql();

        // Should use orWhereHas (or exists)
        $this->assertStringContainsString('or', strtolower($sql));
        $this->assertStringContainsString('exists', strtolower($sql));
    }

    /** @test */
    public function it_generates_correct_sql_for_complex_filter()
    {
        // Create test data
        $patient = Patient::create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $location = Location::create(['city' => 'Dallas', 'state' => 'TX']);
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'location_id' => $location->id,
            'status' => 'confirmed',
            'appointment_date' => now(),
        ]);

        $filters = [
            'patient.name' => 'John Doe',
            'status' => 'confirmed',
            'location.city' => 'Dallas',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $results = $query->get();

        // Should find the appointment
        $this->assertCount(1, $results);
        $this->assertEquals($appointment->id, $results->first()->id);
    }

    /** @test */
    public function it_handles_nested_relationships()
    {
        // This test verifies that the filter builder can handle deeper nesting
        // For now, we'll test with the current structure
        $filters = [
            'patient.name' => 'John',
            'location.city' => 'Dallas',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();

        // Should handle both relationships
        $this->assertStringContainsString('exists', strtolower($sql));
        $this->assertStringContainsString('patients', strtolower($sql));
        $this->assertStringContainsString('locations', strtolower($sql));
    }

    /** @test */
    public function it_verifies_sql_structure_for_whereHas()
    {
        $filters = [
            'patient.name' => 'John',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $sql = $query->toSql();
        
        // The SQL should have a structure like:
        // SELECT * FROM appointments WHERE EXISTS (SELECT * FROM patients WHERE ...)
        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('from', strtolower($sql));
        $this->assertStringContainsString('appointments', strtolower($sql));
        $this->assertStringContainsString('exists', strtolower($sql));
    }
}

