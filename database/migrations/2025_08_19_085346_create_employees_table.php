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
        Schema::create('employees', function (Blueprint $table) {
            $table->bigIncrements('id');
            // $table->string('plant_id')->nullable();
            // $table->string('department_id')->nullable();
            // $table->string('projects_id')->nullable();
            $table->string('designation_id')->nullable();
            $table->string('role_id');
            $table->string('employee_code')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('employee_type');
            $table->string('employee_email');
            $table->string('employee_user_name');
            $table->string('employee_password');
            $table->text('plain_password')->nullable();
            $table->string('reporting_to')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
