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
        // For PostgreSQL, we need to modify the CHECK constraint
        // First, drop the existing constraint
        DB::statement('ALTER TABLE withdrawals DROP CONSTRAINT IF EXISTS withdrawals_status_check');

        // Update any existing 'processing' status to 'approved' for consistency
        DB::statement("UPDATE withdrawals SET status = 'approved' WHERE status = 'processing'");

        // Add the new constraint with 'approved' instead of 'processing'
        DB::statement("ALTER TABLE withdrawals ADD CONSTRAINT withdrawals_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'approved'::text, 'completed'::text, 'rejected'::text, 'cancelled'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE withdrawals DROP CONSTRAINT IF EXISTS withdrawals_status_check');

        // Revert 'approved' back to 'processing'
        DB::statement("UPDATE withdrawals SET status = 'processing' WHERE status = 'approved'");

        // Restore the original constraint
        DB::statement("ALTER TABLE withdrawals ADD CONSTRAINT withdrawals_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'processing'::text, 'completed'::text, 'rejected'::text, 'cancelled'::text]))");
    }
};
