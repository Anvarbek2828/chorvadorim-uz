<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::create('animal_photo_analyses', function (Blueprint $table) {

            $table->id();

            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();

            $table->string('photo_path');

            $table->decimal('bcs_score', 3, 1)->nullable();

            $table->unsignedInteger('estimated_weight')->nullable();

            $table->text('ai_summary')->nullable();

            $table->json('ai_raw_response')->nullable();

            $table->timestamps();

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('animal_photo_analyses');

    }

};

