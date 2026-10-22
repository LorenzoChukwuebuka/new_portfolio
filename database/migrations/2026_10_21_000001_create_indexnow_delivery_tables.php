<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexnow_bulk_runs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_urls')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('indexnow_notifications', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->char('url_hash', 64);
            $table->string('dedupe_bucket', 32);
            $table->string('kind', 16)->default('change');
            $table->string('source', 80)->nullable();
            $table->string('subject_type', 32)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('bulk_run_id')->nullable()
                ->constrained('indexnow_bulk_runs')->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->unsignedSmallInteger('last_response_code')->nullable();
            $table->string('last_error', 64)->nullable();
            $table->timestamps();

            $table->unique(['url_hash', 'dedupe_bucket'], 'indexnow_notifications_dedupe_unique');
            $table->index(['status', 'available_at', 'id'], 'indexnow_notifications_due_index');
            $table->index(['subject_type', 'subject_id', 'status'], 'indexnow_notifications_subject_index');
            $table->index(['bulk_run_id', 'status'], 'indexnow_notifications_run_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexnow_notifications');
        Schema::dropIfExists('indexnow_bulk_runs');
    }
};
