<?php

namespace Backend\Modules\Catalog\Domain\Category\Event;

use Backend\Modules\Catalog\Domain\Category\Category;
use Symfony\Component\EventDispatcher\Event as EventDispatcher;

abstract class Event extends EventDispatcher
{
    /** @var Category */
    private $category;

    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
