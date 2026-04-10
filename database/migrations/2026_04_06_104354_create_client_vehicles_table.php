<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('client_vehicles')) {
            return;
        }

        Schema::create('client_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('car_brand');
            $table->string('car_model');
            $table->string('car_number')->nullable()->index();
            $table->timestamps();
        });

        $timestamp = now();
        $clients = DB::table('clients')
            ->select(['id', 'car_brand', 'car_model', 'car_number'])
            ->whereNotNull('car_brand')
            ->where('car_brand', '!=', '')
            ->whereNotNull('car_model')
            ->where('car_model', '!=', '')
            ->get();

        foreach ($clients as $client) {
            DB::table('client_vehicles')->insert([
                'client_id' => $client->id,
                'car_brand' => $client->car_brand,
                'car_model' => $client->car_model,
                'car_number' => $client->car_number,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_vehicles')) {
            Schema::drop('client_vehicles');
        }
    }
};
