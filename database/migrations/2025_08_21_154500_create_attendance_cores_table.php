<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function(Blueprint $t){
            $t->id();
            $t->string('employee_code')->unique(); // ZKT user id (string)
            $t->string('name');
            $t->string('email')->nullable();
            $t->timestamps();
        });

        Schema::create('shifts', function(Blueprint $t){
            $t->id();
            $t->string('name')->default('General');
            $t->time('start')->default('09:00:00');
            $t->time('end')->default('18:00:00');
            $t->unsignedInteger('grace_minutes')->default(10);
            $t->unsignedInteger('lunch_minutes')->default(60);
            $t->timestamps();
        });

        Schema::create('employee_shift', function(Blueprint $t){
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $t->date('effective_from')->default(now());
            $t->timestamps();
        });

        Schema::create('holidays', function(Blueprint $t){
            $t->id();
            $t->date('date')->unique();
            $t->string('title');
            $t->timestamps();
        });

        Schema::create('leaves', function(Blueprint $t){
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->string('type')->default('Annual');
            $t->enum('status', ['Pending','Approved','Rejected'])->default('Approved');
            $t->timestamps();
            $t->index(['employee_id','start_date','end_date']);
        });

        Schema::create('attendance_raw_logs', function(Blueprint $t){
            $t->id();
            $t->string('employee_code'); // from JSON
            $t->timestamp('punch_time');
            $t->string('device')->default('K60');
            $t->json('payload')->nullable();
            $t->timestamps();
            $t->index(['employee_code','punch_time']);
        });

        Schema::create('attendances', function(Blueprint $t){
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->timestamp('in_time')->nullable();
            $t->timestamp('out_time')->nullable();
            $t->unsignedInteger('work_minutes')->default(0);
            $t->enum('status', ['Present','Late','Half','Leave','Holiday','Absent'])->default('Absent');
            $t->boolean('locked')->default(false);
            $t->timestamps();
            $t->unique(['employee_id','date']);
            $t->index(['date','status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_raw_logs');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('employee_shift');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('employees');
    }
};
