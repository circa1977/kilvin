<?php

namespace Kilvin\Plugins\Weblogs\Templates\Elements;

use Kilvin\Libraries\Twig\Templates\Element as TemplateElement;
use Kilvin\Plugins\Weblogs\Models\Entry as BaseModel;
use Illuminate\Database\Eloquent\Builder;

class Entries extends BaseModel implements \IteratorAggregate
{
    use TemplateElement;

	/**
     * Built in page limit for this element
     *
     * @var integer
     */
    protected static $pageLimit = 20;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // No closed entries can go out
        static::addGlobalScope('live', function (Builder $builder) {
            return BaseModel::scopeLive($builder); // must call scopeLive method directly
        });

        // Default limit
        static::addGlobalScope('pageLimit', function (Builder $builder) {
            $builder->limit(static::$pageLimit);
        });
    }

	/**
     * entry.fields returns an object that outputs the data fields for an entry
     *
     * @return object
     */
    public function getFields($name = '')
    {
    	if (!empty($name)) {
    		return 'My Field: '.$name;
    	}

    	return new class($this->entryData)
    	{
    		public $entryData;

    		public function __construct($entryData)
    		{
    			$this->entryData = $entryData;
    		}

    		/**
		     * Dynamically retrieve attributes on the model.
		     *
		     * @param  string  $key
		     * @return mixed
		     */
		    public function __call($one, $two)
		    {
		        return 'My Field: '.$one;
		    }
    	};
    }
}


