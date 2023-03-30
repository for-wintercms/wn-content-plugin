<?php

namespace ForWinterCms\Content\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * TableCreateForwintercmsContentItems migration
 *
 * @package ForWinterCms\Content\Updates
 */
class TableCreateForwintercmsContentItems extends Migration
{
    public function up()
    {
        Schema::create('forwintercms_content_items', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('page_id');
            $table->string('name', 255);
            $table->text('items')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['page_id', 'name']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('forwintercms_content_items');
    }
}