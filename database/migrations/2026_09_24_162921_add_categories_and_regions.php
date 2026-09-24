<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('group')->index();
            $table->text('blurb');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Nations at the top; England's regions beneath England.
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('phrase'); // "the South West", for "in the South West"
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // No category means "global"; no region means online or UK-wide.
        Schema::table('resources', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('tier')->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('location')->nullable()->after('region_id');
            $table->json('ages')->nullable()->after('location');
            $table->boolean('spotlight')->default(false)->index()->after('ages');
        });

        // Secondary categories (up to two). The primary is resources.category_id.
        Schema::create('category_resource', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_resource');

        // Keys and indexes first, then columns: SQLite won't drop an indexed
        // column, and can't do both in one pass.
        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['region_id']);
            $table->dropIndex(['spotlight']);
        });
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['category_id', 'region_id', 'location', 'ages', 'spotlight']);
        });

        Schema::dropIfExists('regions');
        Schema::dropIfExists('categories');
    }
};
