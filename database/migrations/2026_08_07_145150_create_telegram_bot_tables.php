<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up(): void

    {

        Schema::table('users', function (Blueprint $table) {

            $table->string('telegram_chat_id')->nullable()->unique()->after('region');

        });



        Schema::create('telegram_link_codes', function (Blueprint $table) {

            $table->id();

            $table->string('code', 6)->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->timestamp('expires_at');

            $table->timestamps();

        });



        Schema::create('bank_animal_watchers', function (Blueprint $table) {

            $table->id();

            $table->string('telegram_chat_id');

            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['telegram_chat_id', 'animal_id']);

        });

    }



    public function down(): void

    {

        Schema::dropIfExists('bank_animal_watchers');

        Schema::dropIfExists('telegram_link_codes');

        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn('telegram_chat_id');

        });

    }

};

