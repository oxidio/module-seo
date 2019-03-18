<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Config;
use fn;

class Snippet
{
    public function __invoke(FrontendController $ctrl, Config $conf, array $vars)
    {
        if (!$conf->getConfigParam(GA_ACTIVE)) {
            return null;
        }
        $id   = $conf->getConfigParam(GA_ID);
        $data = strpos($id, 'GTM-') === 0 ? new DataGtm : new DataUa;
        return sprintf(
            $data::SNIPPET,
            json_encode(fn\values($data(new DataLayer($ctrl, $vars))),JSON_PRETTY_PRINT),
            $id
        );
    }
}

