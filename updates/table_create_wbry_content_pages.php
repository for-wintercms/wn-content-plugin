<?php

namespace Wbry\Content\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class TableCreateWbryContentPages extends Migration
{
    public function up()
    {
        Schema::create('wbry_content_pages', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('slug', 255)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wbry_content_pages');
    }
}
