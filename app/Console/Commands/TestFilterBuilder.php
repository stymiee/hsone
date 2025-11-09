<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Location;
use App\Models\Patient;
use App\Services\FilterBuilder;
use Illuminate\Console\Command;
use JsonException;

class TestFilterBuilder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:filter-builder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Filter Builder system with various examples';

    /**
     * Execute the console command.
     * @throws JsonException
     */
    public function handle(): void
    {
        $this->info('=== Filter Builder Demo ===');
        $this->newLine();

        // Create test data
        $this->info('Creating test data...');
        $this->createTestData();
        $this->newLine();

        // Example 1: From requirements
        $this->info('Example 1: Filter from Requirements');
        $this->line('Filters: patient.name = "John", appointment.status = "confirmed", location.city = "Dallas"');
        $this->newLine();

        $filters1 = [
            'patient.name' => 'John',
            'appointment.status' => 'confirmed',
            'location.city' => 'Dallas',
        ];

        $this->displayFilterResults($filters1);
        $this->newLine();

        // Example 2: Direct model field
        $this->info('Example 2: Direct Model Field Filter');
        $this->line('Filter: status = "pending"');
        $this->newLine();

        $filters2 = [
            'status' => 'pending',
        ];

        $this->displayFilterResults($filters2);
        $this->newLine();

        // Example 3: Single relationship filter
        $this->info('Example 3: Single Relationship Filter');
        $this->line('Filter: patient.name = "John"');
        $this->newLine();

        $filters3 = [
            'patient.name' => 'John',
        ];

        $this->displayFilterResults($filters3);
        $this->newLine();

        // Example 4: Multiple relationship filters
        $this->info('Example 4: Multiple Relationship Filters');
        $this->line('Filters: patient.name = "John", location.city = "Dallas"');
        $this->newLine();

        $filters4 = [
            'patient.name' => 'John',
            'location.city' => 'Dallas',
        ];

        $this->displayFilterResults($filters4);
        $this->newLine();

        // Example 5: JSON string input
        $this->info('Example 5: JSON String Input');
        $this->line('Filter: status = "confirmed" (as JSON string)');
        $this->newLine();

        $jsonFilters = json_encode([
                                       'status' => 'confirmed',
                                   ], JSON_THROW_ON_ERROR);

        $query = FilterBuilder::applyFilters(Appointment::query(), $jsonFilters);
        $appointments = $query->get();

        $this->line("  Found {$appointments->count()} appointment(s)");
        foreach ($appointments as $appointment) {
            $this->line("    - Appointment #{$appointment->id}: {$appointment->status} (Patient: {$appointment->patient->name}, Location: {$appointment->location->city})");
        }
        $this->newLine();

        // Example 6: Show SQL
        $this->info('Example 6: Generated SQL');
        $this->line('Filters: patient.name = "John", location.city = "Dallas"');
        $this->newLine();

        $filters6 = [
            'patient.name' => 'John',
            'location.city' => 'Dallas',
        ];

        $query = FilterBuilder::applyFilters(Appointment::query(), $filters6);
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->line('  SQL:');
        $this->line("    {$sql}");
        $this->line('  Bindings:');
        foreach ($bindings as $binding) {
            $this->line("    - " . (is_string($binding) ? $binding : json_encode($binding, JSON_THROW_ON_ERROR)));
        }
        $this->newLine();

        $this->info('=== Demo Complete ===');
    }

    /**
     * Create test data for demonstrations.
     */
    protected function createTestData(): void
    {
        // Create patients
        $patientJohn = Patient::updateOrCreate(
            ['email' => 'john@example.com'],
            ['name' => 'John']
        );

        $patientJane = Patient::updateOrCreate(
            ['email' => 'jane@example.com'],
            ['name' => 'Jane']
        );

        // Create locations
        $locationDallas = Location::updateOrCreate(
            ['city' => 'Dallas'],
            ['state' => 'TX', 'address' => '123 Main St']
        );

        $locationAustin = Location::updateOrCreate(
            ['city' => 'Austin'],
            ['state' => 'TX', 'address' => '456 Oak Ave']
        );

        // Create appointments
        Appointment::updateOrCreate(
            [
                'patient_id' => $patientJohn->id,
                'location_id' => $locationDallas->id,
                'appointment_date' => now(),
            ],
            [
                'status' => 'confirmed',
                'notes' => 'Test appointment 1',
            ]
        );

        Appointment::updateOrCreate(
            [
                'patient_id' => $patientJohn->id,
                'location_id' => $locationAustin->id,
                'appointment_date' => now()->addDay(),
            ],
            [
                'status' => 'pending',
                'notes' => 'Test appointment 2',
            ]
        );

        Appointment::updateOrCreate(
            [
                'patient_id' => $patientJane->id,
                'location_id' => $locationDallas->id,
                'appointment_date' => now()->addDays(2),
            ],
            [
                'status' => 'confirmed',
                'notes' => 'Test appointment 3',
            ]
        );

        $this->line('  ✓ Created test patients, locations, and appointments');
    }

    /**
     * Display filter results.
     */
    protected function displayFilterResults(array $filters): void
    {
        $query = FilterBuilder::applyFilters(Appointment::query(), $filters);
        $appointments = $query->get();

        $this->line("  Found {$appointments->count()} appointment(s):");

        if ($appointments->isEmpty()) {
            $this->line('    (No appointments match the filters)');
        } else {
            foreach ($appointments as $appointment) {
                $patientName = $appointment->patient->name ?? 'N/A';
                $locationCity = $appointment->location->city ?? 'N/A';
                $this->line("    - Appointment #{$appointment->id}:");
                $this->line("      Status: {$appointment->status}");
                $this->line("      Patient: {$patientName}");
                $this->line("      Location: {$locationCity}");
            }
        }
    }
}
