<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who closed the ticket, and when the requester took it off their list.
     * Archiving only hides the ticket from the requester; nothing is deleted.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('closed_by_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('requester_archived_at')->nullable()->after('closed_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_id');
            $table->dropColumn('requester_archived_at');
        });
    }
};
