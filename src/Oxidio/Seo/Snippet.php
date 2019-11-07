<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model\ArticleList;
use OxidEsales\Eshop\Core\Config;
use Php;
use Psr\Log\LoggerInterface;
use Throwable;

class Snippet
{
    public function __invoke(FrontendController $ctrl, Config $conf, LoggerInterface $logger, ArticleList ...$lists)
    {
        if (!($id = $conf->getConfigParam(GA_ID)) || !$conf->getConfigParam(GA_ACTIVE)) {
            return null;
        }
        $data = strpos($id, 'GTM-') === 0 ? new DataGtm : new DataUa;
        try {
            return sprintf(
                $data::SNIPPET,
                json_encode(Php::values($data(new DataLayer($ctrl, ...$lists))),JSON_PRETTY_PRINT),
                $id
            );
        } catch (Throwable $e) {
            $logger->error($e->getMessage());
            return false;
        }
    }
}

