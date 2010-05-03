<?php

namespace Documents;

/** @Document */
class Configuration
{
    /** @Field */
    private $timezone;

    /** @Field */
    private $theme;

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }
}