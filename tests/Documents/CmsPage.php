<?php

namespace Documents;

/**
 * @MappedSuperclass
 * @Indexes({
 *   @Index(keys={"slug"="asc"}, options={"unique"="true"})
 * })
 */
abstract class CmsPage
{
    /**
     * @Id
     */
    public $id;

    /**
     * @String
     */
    public $slug;
}
