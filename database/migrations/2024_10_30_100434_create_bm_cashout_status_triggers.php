<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop existing trigger if it exists
        DB::unprepared('DROP TRIGGER IF EXISTS before_bm_cashout_status_update');

        // Create trigger for updates on bm_cashout_status table
        DB::unprepared('
            CREATE TRIGGER before_bm_cashout_status_update
            BEFORE UPDATE ON bm_cashout_status
            FOR EACH ROW
            BEGIN
                -- Only update message_id, keep the same transaction_id
                SET NEW.message_id = LEFT(CONCAT(generate_padded_number(), "-", NEW.transaction_id), 50);
            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop trigger
        DB::unprepared('DROP TRIGGER IF EXISTS before_bm_cashout_status_update');
    }
};