<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']);
        $invoiceDate = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = fake()->dateTimeBetween($invoiceDate, '+30 days');

        return [
            'company_id' => \App\Models\Company::factory(),
            'customer_id' => \App\Models\Customer::factory(),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'status' => $status,
            'subtotal' => fake()->randomFloat(2, 50, 1000),
            'tax_rate' => fake()->randomFloat(1, 0, 10),
            'tax_amount' => 0, // Will be calculated
            'total' => 0, // Will be calculated
            'notes' => fake()->optional(0.3)->sentence(),
            'sent_at' => $status === 'sent' || $status === 'paid' ? fake()->dateTimeBetween($invoiceDate, 'now') : null,
            'paid_at' => $status === 'paid' ? fake()->dateTimeBetween($invoiceDate, 'now') : null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'sent_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
            'paid_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? $attributes['invoice_date'], 'now'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'due_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'sent_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'paid_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }
}
