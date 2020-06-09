<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

class DataGtm
{
    public function __invoke(DataLayer $dataLayer)
    {
        return $dataLayer;
    }

    public const SNIPPET
        = <<<EOT

<!-- Google Tag Manager -->
<script>

    window.dataLayer = window.dataLayer || [];
    %s.forEach(function(data) {
        window.dataLayer.push(data);
    });

    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','%s');

</script>
<!-- End Google Tag Manager -->

EOT;

}
