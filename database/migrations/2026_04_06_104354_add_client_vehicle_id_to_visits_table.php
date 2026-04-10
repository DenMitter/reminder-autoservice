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
        $this->ensureClientVehiclesTableExists();

        if (! Schema::hasColumn('visits', 'client_vehicle_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->foreignId('client_vehicle_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            });
        }

        $visits = DB::table('visits')
            ->select(['id', 'client_id'])
            ->get();

        foreach ($visits as $visit) {
            $clientVehicleId = DB::table('client_vehicles')
                ->where('client_id', $visit->client_id)
                ->orderBy('id')
                ->value('id');

            DB::table('visits')
                ->where('id', $visit->id)
                ->update([
                    'client_vehicle_id' => $clientVehicleId,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('visits', 'client_vehicle_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->dropConstrainedForeignId('client_vehicle_id');
            });
        }
    }

    private function ensureClientVehiclesTableExists(): void
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
};
