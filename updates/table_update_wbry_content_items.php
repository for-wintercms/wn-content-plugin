<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class TableUpdateWbryContentItems extends Migration
{
    public function up()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->string('title', 255)->after('name');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->dropColumn('title');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
}