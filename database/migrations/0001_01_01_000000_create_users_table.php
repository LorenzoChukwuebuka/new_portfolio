<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            $table->longText('content');
            $table->string('author')->default('Lorenzo Obi');
            $table->integer('read_time')->default(5); // in minutes
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->json('meta_data')->nullable(); // SEO metadata
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('full_description')->nullable();
            $table->string('project_link'); // URL to live project
            $table->string('github_link')->nullable();
            $table->json('technologies'); // Array of tech stack
            $table->enum('status', ['completed', 'in-progress', 'archived'])->default('completed');
            $table->date('completed_at')->nullable();
            $table->integer('order')->default(0); // For manual sorting
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index('slug');
            $table->index('is_featured');
            $table->index('order');
        });

        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "Resume 2026", "Full Stack Developer CV"
            $table->string('version')->default('1.0');
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('is_active');
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('ip_address')->nullable();
            $table->enum('status', ['unread', 'read', 'replied'])->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            // $table->morphs('tagable'); // Polymorphic relation one to many or  one to one
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tagables', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned();
            $table->morphs('tagable');
            $table->primary(['tag_id', 'tagable_id', 'tagable_type']);
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });

        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('mediable'); // This creates mediable_id and mediable_type columns
            $table->string('url');
            $table->string('type')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('encoded')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::insert('INSERT INTO users (name, email, email_verified_at, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)', [
            'Lorenzo Chukwuebuka Obi',
            'lawrenceobi2@gmail.com',
            now(),
            bcrypt('changeMe123#!'),
            now(),
            now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('media');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('cvs');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('contact_messages');
    }
};