<?php namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateWbryContentItems extends Migration
{
    public function up()
    {
        Schema::create('wbry_content_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('items')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('wbry_content_items');
    }
}
