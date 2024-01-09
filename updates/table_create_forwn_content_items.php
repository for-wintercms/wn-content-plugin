<?php

namespace ForWinterCms\Content\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

/**
 * TableCreateForwnContentItems migration
 *
 * @package ForWinterCms\Content\Updates
 */
class TableCreateForwnContentItems extends Migration
{
    public function up()
    {
        Schema::create('forwn_content_items', function(Blueprint $table)
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
        Schema::dropIfExists('forwn_content_items');
    }
}