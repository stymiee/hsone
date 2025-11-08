<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    protected RuleEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new RuleEvaluator();
    }

    /** @test */
    public function it_evaluates_equality_operator()
    {
        $user = User::factory()->create(['role' => 'staff']);

        $ruleSet = [
            'action' => 'submit_form',
            'rules' => [
                [
                    'field' => 'role',
                    'operator' => '==',
                    'value' => 'staff',
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        $user->role = 'admin';
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_not_equals_operator()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $ruleSet = [
            'action' => 'submit_form',
            'rules' => [
                [
                    'field' => 'email_verified_at',
                    'operator' => '!=',
                    'value' => null,
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        $user->email_verified_at = null;
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_in_operator()
    {
        $user = User::factory()->create(['role' => 'staff']);

        $ruleSet = [
            'action' => 'access_admin',
            'rules' => [
                [
                    'field' => 'role',
                    'operator' => 'in',
                    'value' => ['admin', 'staff', 'manager'],
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        $user->role = 'guest';
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_not_in_operator()
    {
        $user = User::factory()->create(['role' => 'guest']);

        $ruleSet = [
            'action' => 'access_content',
            'rules' => [
                [
                    'field' => 'role',
                    'operator' => 'not_in',
                    'value' => ['banned', 'suspended'],
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        $user->role = 'banned';
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_greater_than_operator()
    {
        // Add a numeric field for testing (e.g., age or points)
        $user = User::factory()->create();

        // Using ID as a numeric field for demonstration
        $ruleSet = [
            'action' => 'premium_access',
            'rules' => [
                [
                    'field' => 'id',
                    'operator' => '>',
                    'value' => 0,
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_less_than_operator()
    {
        $user = User::factory()->create();

        $ruleSet = [
            'action' => 'new_user',
            'rules' => [
                [
                    'field' => 'id',
                    'operator' => '<',
                    'value' => 1000,
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_contains_operator()
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $ruleSet = [
            'action' => 'search',
            'rules' => [
                [
                    'field' => 'name',
                    'operator' => 'contains',
                    'value' => 'John',
                ],
            ],
        ];

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        $user->name = 'Jane Smith';
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_evaluates_multiple_rules_with_and_logic()
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

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

        $this->assertTrue($this->evaluator->evaluate($user, $ruleSet));

        // Fail first rule
        $user->role = 'admin';
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));

        // Pass first rule, fail second rule
        $user->role = 'staff';
        $user->email_verified_at = null;
        $user->save();
        $this->assertFalse($this->evaluator->evaluate($user, $ruleSet));
    }

    /** @test */
    public function it_accepts_json_string_rule_set()
    {
        $user = User::factory()->create(['role' => 'staff']);

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

        $this->assertTrue($this->evaluator->evaluate($user, $jsonRuleSet));
    }

    /** @test */
    public function it_returns_false_for_invalid_rule_set()
    {
        $user = User::factory()->create();

        // Missing rules array
        $invalidRuleSet = [
            'action' => 'submit_form',
        ];

        $this->assertFalse($this->evaluator->evaluate($user, $invalidRuleSet));

        // Missing field
        $invalidRuleSet2 = [
            'action' => 'submit_form',
            'rules' => [
                [
                    'operator' => '==',
                    'value' => 'staff',
                ],
            ],
        ];

        $this->assertFalse($this->evaluator->evaluate($user, $invalidRuleSet2));
    }
}

