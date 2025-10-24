<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 0.5, 5);
        $unitPrice = fake()->randomFloat(2, 25, 200);
        $lineTotal = $quantity * $unitPrice;

        return [
            'invoice_id' => \App\Models\Invoice::factory(),
            'service_appointment_id' => \App\Models\ServiceAppointment::factory(),
            'description' => fake()->sentence(4),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ];
    }
}
