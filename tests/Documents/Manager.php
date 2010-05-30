<?php

namespace Documents;

/** @Document(db="my_db", collection="managers") */
class Manager extends BaseEmployee
{
    /** @ReferenceMany(targetDocument="Documents\Project") */
    private $projects = array();

    public function getProjects()
    {
        return $this->projects;
    }

    public function addProject(Project $project)
    {
        $this->projects[] = $project;
    }
}