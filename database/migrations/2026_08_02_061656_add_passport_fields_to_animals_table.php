
<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration

{

    public function up(): void

    {

        Schema::table('animals', function (Blueprint $table) {

            $table->boolean('has_passport')->default(false)->after('notes');

            $table->string('passport_number')->nullable()->after('has_passport');

            $table->string('chip_number')->nullable()->after('passport_number');

            $table->date('passport_issue_date')->nullable()->after('chip_number');

        });

    }

    public function down(): void

    {

        Schema::table('animals', function (Blueprint $table) {

            $table->dropColumn(['has_passport', 'passport_number', 'chip_number', 'passport_issue_date']);

        });

    }

};

