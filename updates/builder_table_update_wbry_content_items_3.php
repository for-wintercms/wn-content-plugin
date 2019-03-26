<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateWbryContentItems3 extends Migration
{
    public function up()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->string('page');
        });
    }
    
    public function down()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->dropColumn('page');
        });
    }
}
