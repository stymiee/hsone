<?php

namespace App\Examples;

use App\Models\User;
use App\Services\RuleEvaluator;

/**
 * Example usage of the RuleEvaluator class.
 * 
 * This demonstrates how to use the RuleEvaluator to check if a user
 * can perform a given action based on JSON rules.
 */
class RuleEvaluatorExample
{
    public static function runExample(): void
    {
        $evaluator = new RuleEvaluator();

        // Example rule set from the requirements
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

        // Create a user that matches the rules
        $user1 = new User();
        $user1->role = 'staff';
        $user1->email_verified_at = now();

        // Create a user that doesn't match the rules
        $user2 = new User();
        $user2->role = 'admin';
        $user2->email_verified_at = null;

        // Evaluate
        $canUser1Submit = $evaluator->evaluate($user1, $ruleSet);
        $canUser2Submit = $evaluator->evaluate($user2, $ruleSet);

        echo "User 1 (staff, verified) can submit form: " . ($canUser1Submit ? 'YES' : 'NO') . "\n";
        echo "User 2 (admin, not verified) can submit form: " . ($canUser2Submit ? 'YES' : 'NO') . "\n";

        // Example with JSON string
        $jsonRuleSet = json_encode($ruleSet);
        $canUser1SubmitJson = $evaluator->evaluate($user1, $jsonRuleSet);
        echo "User 1 with JSON rule set: " . ($canUser1SubmitJson ? 'YES' : 'NO') . "\n";
    }
}

