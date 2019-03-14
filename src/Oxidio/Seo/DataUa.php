<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use fn;

class DataUa
{
    public function __invoke(DataLayer $dataLayer)
    {
        fn\fail('todo: implement it');
    }

    public const SNIPPET = <<<EOT

<!-- Google Analytics -->
<script>

    window.dataLayer = window.dataLayer || [];
    
    dataLayer.push(%s);

    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
    
    ga('create', '%s', 'auto');
    ga('require', 'ec');

</script>
<!-- End Google Analytics -->

EOT;
}
