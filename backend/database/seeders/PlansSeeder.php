<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'               => 'Free',
                'slug'               => 'free',
                'billing_type'       => 'free',
                'price'              => 0,
                'max_messages_day'   => 5,
                'max_tokens_month'   => 20000,
                'max_prompt_length'  => 1000,
                'max_response_length'=> 1000,
                'tickets_per_charge' => 0,
                'sort_order'         => 1,
            ],
            [
                'name'               => 'Basic',
                'slug'               => 'basic',
                'billing_type'       => 'per_ticket',
                'price'              => 9.99,
                'max_messages_day'   => 50,
                'max_tokens_month'   => 500000,
                'max_prompt_length'  => 4000,
                'max_response_length'=> 4000,
                'tickets_per_charge' => 10,
                'sort_order'         => 2,
            ],
            [
                'name'               => 'Pro',
                'slug'               => 'pro',
                'billing_type'       => 'monthly',
                'price'              => 49.99,
                'max_messages_day'   => 200,
                'max_tokens_month'   => 3000000,
                'max_prompt_length'  => 8000,
                'max_response_length'=> 8000,
                'tickets_per_charge' => 0,
                'sort_order'         => 3,
            ],
            [
                'name'               => 'Enterprise',
                'slug'               => 'enterprise',
                'billing_type'       => 'monthly',
                'price'              => 199.99,
                'max_messages_day'   => 10000,
                'max_tokens_month'   => 50000000,
                'max_prompt_length'  => 32000,
                'max_response_length'=> 16000,
                'tickets_per_charge' => 0,
                'sort_order'         => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
