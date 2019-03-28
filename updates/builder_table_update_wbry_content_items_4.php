<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateWbryContentItems4 extends Migration
{
    public function up()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->string('name', 255);
            $table->string('repeater', 255);
            $table->dropColumn('title');
            $table->dropColumn('slug');
        });
    }
    
    public function down()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->dropColumn('name');
            $table->dropColumn('repeater');
            $table->string('title', 255);
            $table->string('slug', 255);
        });
    }
}
