<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio
{
    function seo(): Seo\Module {
        static $module;
        return $module ?: $module = new Seo\Module;
    }
}
