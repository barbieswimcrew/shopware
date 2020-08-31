<?php

namespace MollieShopware\Components\ApplePayDirect\Services;

class ApplePayDomainFileDownloader
{
    
    /**
     * @param $docRoot
     * @return mixed|void
     */
    public function downloadDomainAssociationFile($docRoot)
    {
        $content = file_get_contents('https://www.mollie.com/.well-known/apple-developer-merchantid-domain-association');

        $appleFolder = $docRoot . '/.well-known';

        if (!file_exists($appleFolder)) {
            mkdir($appleFolder);
        }

        file_put_contents($appleFolder . '/apple-developer-merchantid-domain-association', $content);
    }
    
}
