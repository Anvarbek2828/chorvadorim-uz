
<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

return new class extends Migration

{

    public function up(): void

    {

        Schema::create('animals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name');

            $table->enum('direction', ['sut', 'gosht', 'nasl']);

            $table->string('breed')->nullable();

            $table->date('birth_date')->nullable();

            $table->decimal('initial_weight', 8, 2)->nullable();

            $table->decimal('current_weight', 8, 2)->nullable();

            $table->string('gender')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

        });

    }

    public function down(): void

    {

        Schema::dropIfExists('animals');

    }

};

