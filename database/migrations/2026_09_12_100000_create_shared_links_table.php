<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A shortlist of hoardings the Brand Adda team has picked out for one client,
 * reachable at /shared/{token} and nowhere else.
 *
 * The chosen media live in their own rows rather than a JSON column on the
 * link: the listing page runs them back through the same search query the
 * /search page uses, which wants a real set of ids to join against, and a
 * media row that is later deleted can then be cleaned up by id.
 *
 * Links do not expire. Deleting the row is what revokes one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shared_links')) {
            Schema::create('shared_links', function (Blueprint $table) {
                $table->id();

                // What appears in the URL. Random rather than sequential so a
                // client cannot reach somebody else's shortlist by counting.
                $table->string('token', 40)->unique();

                // Optional label so the team can tell shortlists apart.
                $table->string('title')->nullable();

                // users.id of the admin who generated it. Nullable so removing
                // a staff account never takes a client's live link down.
                $table->unsignedBigInteger('created_by')->nullable();

                $table->timestamps();

                $table->index('created_by');
            });
        }

        if (!Schema::hasTable('shared_link_items')) {
            Schema::create('shared_link_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('shared_link_id');
                $table->unsignedBigInteger('media_id');
                $table->timestamps();

                // The same hoarding cannot be shortlisted twice on one link,
                // and this is also the lookup the listing page makes.
                $table->unique(['shared_link_id', 'media_id']);
                $table->index('media_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_link_items');
        Schema::dropIfExists('shared_links');
    }
};
