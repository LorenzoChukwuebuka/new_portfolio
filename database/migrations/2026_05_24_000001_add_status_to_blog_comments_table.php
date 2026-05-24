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
        if (! Schema::hasColumn('blog_comments', 'status')) {
            Schema::table('blog_comments', function (Blueprint $table) {
                $table->string('status', 20)->default('pending')->index()->after('is_approved');
            });

            DB::table('blog_comments')
                ->where('is_approved', true)
                ->update(['status' => 'approved']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('blog_comments', 'status')) {
            Schema::table('blog_comments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
