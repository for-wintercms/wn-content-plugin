<?php

namespace Wbry\Content\Models;

use Model;
use October\Rain\Database\Builder;
use Wbry\Content\Classes\ContentItems;
use Wbry\Content\Models\Page as PageModel;

/**
 * Item model
 *
 * @package Wbry\Content\Models
 * @author Wbry, Diamond <me@diamondsystems.org>
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $implement = [];

    public $table = 'wbry_content_items';

    protected $jsonable = ['items'];

    public $translatable = ['items'];

    public $fillable = ['page_id', 'name', 'items'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'page_id' => 'required|numeric',
        'name'    => 'required|between:1,256|alpha_dash',
    ];

    public $attributeNames = [
        'name' => 'wbry.content::content.items.name_label',
    ];

    /*
     * Scopes
     */

    public function scopePage(Builder $query, string $page)
    {
        $query->where('page_id', PageModel::where('slug', $page)->value('id'));
    }

    public function scopeItem(Builder $query, string $page, string $name)
    {
        $query->page($page)->where('name', $name);
    }

    /*
     * Dropdown
     */

    public function formListItems($keyValue = null, $fieldName = null)
    {
        $return = [
            '' => '---'
        ];

        if ($this->page_id && $this->name)
        {
            $list = self::where('page_id', $this->page_id)->where('name', '!=', $this->name)->lists('name', 'name');
            if (! $list)
                return $return;

            $return = array_merge($return, $list);
            $items = ContentItems::instance();
            $itemsList = $items->getItemsList(PageModel::where('id', $this->page_id)->value('slug'));

            if (count($itemsList))
            {
                foreach ($return as &$item)
                {
                    if (isset($itemsList[$item]))
                        $item = $itemsList[$item]['title'];
                }
            }
        }

        return $return;
    }
}
