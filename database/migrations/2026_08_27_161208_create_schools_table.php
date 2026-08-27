<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kelurahan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('school_type');
            $table->string('npsn')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('condition')->default('baik');
            $table->unsignedInteger('students_male')->default(0);
            $table->unsignedInteger('students_female')->default(0);
            $table->unsignedInteger('teachers')->default(0);
            $table->unsignedInteger('classes')->default(0);
            $table->unsignedInteger('capacity')->default(0);
            $table->decimal('library_percentage', 5, 2)->default(0);
            $table->decimal('science_lab_percentage', 5, 2)->default(0);
            $table->decimal('computer_lab_percentage', 5, 2)->default(0);
            $table->decimal('teacher_room_percentage', 5, 2)->default(0);
            $table->decimal('toilet_percentage', 5, 2)->default(0);
            $table->decimal('worship_room_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kecamatan_id', 'school_type']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
