<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_units', function (Blueprint $table) {
            $table->string('name')->nullable()->after('status');
        });

        DB::table('room_units')->update([
            'name' => DB::raw('CAST(unit_number AS CHAR)')
        ]);

        Schema::table('room_units', function (Blueprint $table) {
            $table->dropColumn('unit_number');
            $table->string('name')->nullable(false)->change();
        });
    }

    public function down(): void {}
};
