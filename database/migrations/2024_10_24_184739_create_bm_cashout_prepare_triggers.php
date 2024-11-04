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
        // Drop existing functions and triggers if they exist
        DB::unprepared('DROP TRIGGER IF EXISTS before_bm_cashout_prepare_insert');
        DB::unprepared('DROP FUNCTION IF EXISTS generate_padded_number');
        DB::unprepared('DROP FUNCTION IF EXISTS generate_uuid_v4');

        // Create function for padded number generation
        DB::unprepared('
            CREATE FUNCTION generate_padded_number() 
            RETURNS VARCHAR(14)
            DETERMINISTIC
            BEGIN
                DECLARE next_val INT;
                SET next_val = (SELECT IFNULL(MAX(SUBSTRING_INDEX(message_id, "-", 1)), 0) + 1 FROM bm_cashout_prepare);
                RETURN LPAD(next_val, 14, "0");
            END
        ');

        // Create function for UUID generation
        DB::unprepared('
            CREATE FUNCTION generate_uuid_v4()
            RETURNS VARCHAR(35)
            DETERMINISTIC
            BEGIN
                RETURN LEFT(UUID(), 35); -- Truncate UUID to 35 characters
            END
        ');

        // Create trigger for new inserts
        DB::unprepared('
            CREATE TRIGGER before_bm_cashout_prepare_insert
            BEFORE INSERT ON bm_cashout_prepare
            FOR EACH ROW
            BEGIN
                -- Only set transaction_id if it\'s NULL
                IF NEW.transaction_id IS NULL THEN
                    SET NEW.transaction_id = generate_uuid_v4();
                END IF;
                
                -- Always generate a new message_id within 50 characters
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
        // Drop triggers and functions in reverse order
        DB::unprepared('DROP TRIGGER IF EXISTS before_bm_cashout_prepare_insert');
        DB::unprepared('DROP FUNCTION IF EXISTS generate_padded_number');
        DB::unprepared('DROP FUNCTION IF EXISTS generate_uuid_v4');
    }
};
