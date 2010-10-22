<?php

namespace Documents;

/**
 * @MappedSuperclass
 */
abstract class CmsContent extends CmsPage
{
    /**
     * @String
     */
    public $title;
}
