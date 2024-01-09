<?php

namespace ForWinterCms\Content\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

/**
 * TableCreateForwnContentTranslateItems migration
 *
 * @package ForWinterCms\Content\Updates
 */
class TableCreateForwnContentTranslateItems extends Migration
{
    public function up()
    {
        if (Schema::hasTable('forwintercms_content_items'))
            Schema::rename('forwintercms_content_items', 'forwn_content_items');
        if (Schema::hasTable('forwintercms_content_pages'))
            Schema::rename('forwintercms_content_pages', 'forwn_content_pages');

        Schema::create('forwn_content_translate_items', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('item_id');
            $table->string('locale', 255);
            $table->text('items')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('forwn_content_translate_items');
    }
}