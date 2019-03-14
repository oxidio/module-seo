<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use OxidEsales\Eshop\Core\Controller\BaseController;
use OxidEsales\Eshop\Core\Registry;
use fn;

function gaSnippet() {
    $vars = Registry::getUtilsView()->getSmarty()->get_template_vars();
    /** @var BaseController $ctrl */
    $ctrl = $vars['oView'];
    $conf = $ctrl->getConfig();
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
