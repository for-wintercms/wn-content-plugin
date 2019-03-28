<?php

namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

/**
 * TableCreateWbryContentItems migration
 *
 * @package Wbry\Content\Updates
 * @author Diamond Systems
 */
class TableCreateWbryContentItems extends Migration
{
    public function up()
    {
        Schema::create('wbry_content_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('page');
            $table->string('name', 255);
            $table->string('repeater', 255);
            $table->text('items')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('wbry_content_items');
    }
}
