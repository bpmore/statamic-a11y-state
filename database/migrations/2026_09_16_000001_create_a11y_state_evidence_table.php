<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The evidence log's one table. Run by `php please a11y:state:install` on
 * the connection the config file names, never by `loadMigrationsFrom`, so
 * there is one path and one record of what ran, and a flat-file site that
 * keeps its evidence in YAML never has this table.
 *
 * The id is the primary key, which is the database's own refusal to write
 * a record twice. There is no `updated_at`: a row is never updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('a11y_state_evidence', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            // Kept as the ISO 8601 string the record carries, so what is
            // stored is byte for byte what the YAML driver would store and
            // the two drivers export the same file.
            $table->string('at', 32)->index();
            $table->string('actor');
            $table->string('action', 32)->index();
            $table->string('criterion', 16)->nullable()->index();
            $table->string('subject', 2048)->nullable();
            $table->text('notes');
            $table->string('supersedes', 64)->nullable()->index();
            $table->json('meta');
            $table->string('created_at', 32);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('a11y_state_evidence');
    }
};
