<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use fn;
use Oxidio\Module\Settings;
use Psr\Container\ContainerInterface;

/**
 * @property-read array $module
 */
class Module
{
    use fn\DI\PropertiesReadOnlyTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct()
    {
        call_user_func(require VENDOR_PATH . '/autoload.php', function (ContainerInterface $container) {
            $this->container = fn\di(__DIR__ . '/../../../config/di.php', $container);
        });
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param string $lang
     *
     * @return array
     */
    public function getTranslations(string $lang): array
    {
        return fn\traverse($this->container->get(Settings::class)->translate($lang));
    }
}
