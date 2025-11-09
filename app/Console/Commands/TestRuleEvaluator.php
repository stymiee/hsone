<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\RuleEvaluator;
use Illuminate\Console\Command;
use JsonException;

class TestRuleEvaluator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rule-evaluator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Rule Evaluator system with various examples';

    /**
     * Execute the console command.
     * @throws JsonException
     */
    public function handle(): void
    {
        $evaluator = new RuleEvaluator();

        $this->info('=== Rule Evaluator Demo ===');
        $this->newLine();

        // Create test users
        $this->info('Creating test users...');
        $staffUser = User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => bcrypt('password'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );
        $staffUser->save();

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        $adminUser->save();

        $unverifiedUser = User::updateOrCreate(
            ['email' => 'unverified@example.com'],
            [
                'name' => 'Unverified User',
                'password' => bcrypt('password'),
                'role' => 'staff',
                'email_verified_at' => null,
            ]
        );
        $unverifiedUser->save();

        // Refresh to ensure we have the latest data
        $staffUser = $staffUser->fresh();
        $adminUser = $adminUser->fresh();
        $unverifiedUser = $unverifiedUser->fresh();

        $this->info("✓ Created/Updated users: {$staffUser->name}, {$adminUser->name}, {$unverifiedUser->name}");
        $this->newLine();

        // Example 1: From requirements
        $this->info('Example 1: Submit Form Rule (from requirements)');
        $this->line('Rule: role == "staff" AND email_verified_at != null');
        $this->newLine();

        $ruleSet1 = [
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

        $this->displayResult('Staff User (staff, verified)', $staffUser, $ruleSet1, $evaluator);
        $this->displayResult('Admin User (admin, verified)', $adminUser, $ruleSet1, $evaluator);
        $this->displayResult('Unverified User (staff, not verified)', $unverifiedUser, $ruleSet1, $evaluator);
        $this->newLine();

        // Example 2: Using "in" operator
        $this->info('Example 2: Access Admin Panel');
        $this->line('Rule: role IN ["admin", "staff", "manager"]');
        $this->newLine();

        $ruleSet2 = [
            'action' => 'access_admin',
            'rules' => [
                [
                    'field' => 'role',
                    'operator' => 'in',
                    'value' => ['admin', 'staff', 'manager'],
                ],
            ],
        ];

        $this->displayResult('Staff User', $staffUser, $ruleSet2, $evaluator);
        $this->displayResult('Admin User', $adminUser, $ruleSet2, $evaluator);
        $this->newLine();

        // Example 3: Using "contains" operator
        $this->info('Example 3: Name Contains');
        $this->line('Rule: name CONTAINS "Staff"');
        $this->newLine();

        $ruleSet3 = [
            'action' => 'search',
            'rules' => [
                [
                    'field' => 'name',
                    'operator' => 'contains',
                    'value' => 'Staff',
                ],
            ],
        ];

        $this->displayResult('Staff User', $staffUser, $ruleSet3, $evaluator);
        $this->displayResult('Admin User', $adminUser, $ruleSet3, $evaluator);
        $this->newLine();

        // Example 4: Using JSON string
        $this->info('Example 4: Using JSON String Input');
        $this->line('Rule: role == "admin"');
        $this->newLine();

        $jsonRuleSet = json_encode([
                                       'action' => 'admin_action',
                                       'rules'  => [
                                           [
                                               'field' => 'role',
                                                                 'operator' => '==',
                                               'value' => 'admin',
                                           ],
                                       ],
                                   ], JSON_THROW_ON_ERROR);

        $this->displayResult('Staff User (JSON input)', $staffUser, $jsonRuleSet, $evaluator);
        $this->displayResult('Admin User (JSON input)', $adminUser, $jsonRuleSet, $evaluator);
        $this->newLine();

        // Example 5: Multiple rules with different operators
        $this->info('Example 5: Complex Rule with Multiple Conditions');
        $this->line('Rule: role == "staff" AND email_verified_at != null AND id > 0');
        $this->newLine();

        $ruleSet5 = [
            'action' => 'complex_action',
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
                [
                    'field' => 'id',
                    'operator' => '>',
                    'value' => 0,
                ],
            ],
        ];

        $this->displayResult('Staff User', $staffUser, $ruleSet5, $evaluator);
        $this->newLine();

        $this->info('=== Demo Complete ===');
    }

    /**
     * Display the result of a rule evaluation.
     */
    protected function displayResult(string $label, User $user, array|string $ruleSet, RuleEvaluator $evaluator): void
    {
        $result = $evaluator->evaluate($user, $ruleSet);
        $status = $result ? '✓ ALLOWED' : '✗ DENIED';
        $color = $result ? 'green' : 'red';

        $this->line("  {$label}: ");
        $this->line("    Role: " . ($user->role ?? 'null'));
        $this->line("    Email Verified: " . ($user->email_verified_at !== null ? 'Yes' : 'No'));
        $this->line("    Result: <fg={$color}>{$status}</>");
        $this->newLine();
    }
}
