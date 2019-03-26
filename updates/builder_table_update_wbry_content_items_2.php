<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateWbryContentItems2 extends Migration
{
    public function up()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->string('slug');
            $table->renameColumn('page', 'title');
        });
    }
    
    public function down()
    {
        Schema::table('wbry_content_items', function($table)
        {
            $table->dropColumn('slug');
            $table->renameColumn('title', 'page');
        });
    }
}
