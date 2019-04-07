<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class TableUpdateWbryContentItems2 extends Migration
{
    public function up()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->dropColumn('title');
            $table->dropColumn('repeater');
        });
    }
    
    public function down()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->string('title', 255)->after('name');
            $table->string('repeater', 255);
        });
    }
}