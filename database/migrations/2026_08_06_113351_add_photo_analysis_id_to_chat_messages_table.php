<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::table('chat_messages', function (Blueprint $table) {

            $table->foreignId('photo_analysis_id')->nullable()->after('animal_id')

                ->constrained('animal_photo_analyses')->nullOnDelete();

        });

    }



    public function down(): void

    {

        Schema::table('chat_messages', function (Blueprint $table) {

            $table->dropForeign(['photo_analysis_id']);

            $table->dropColumn('photo_analysis_id');

        });

    }

};

