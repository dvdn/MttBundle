<?php

/**
 * Symfony service to call the pdfGenerator webservice
 *
 * @author vdegroote
 */
namespace CanalTP\MethBundle\Services;

use Symfony\Component\Filesystem\Filesystem;

class PdfGenerator
{
    private $serverUrl = null;
    private $layoutsConfig = null;

    public function __construct($server, $layoutsConfig)
    {
        $this->serverUrl = $server;
        $this->layoutsConfig = $layoutsConfig;
    }
    
    // calls the webservice http://hg.prod.canaltp.fr/ctp/pdfGenerator.git/summary
    private function callWebservice($url)
    {
        $ch = curl_init();

        // set URL and other appropriate options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 

        // grab URL and pass it to the browser
        $pdfContent = curl_exec($ch);

        // close cURL resource, and free up system resources
        curl_close($ch);
        return $pdfContent;
    }
    
    public function getPdf($url, $layout)
    {
        $params = array();
        $params['url'] = $url;
        if (isset($this->layoutsConfig[$layout]))
        {
            $params['orientation'] = $this->layoutsConfig[$layout]['orientation'];
        }
        // TODO: make these parameters configurable via layout config?
        $params['zoom'] = 2;
        $params['margin'] = 0;
        $generation_url = $this->serverUrl . '?' . http_build_query($params);
        
        $pdfContent = $this->callWebservice($generation_url);
        
        // create File
        $dir = sys_get_temp_dir() . '/';
        $filename = md5($pdfContent) . '.pdf';
        $path = $dir . $filename;
        $fs = new Filesystem();
        $fs->dumpFile($path, $pdfContent);
        
        // var_dump($pdfContent, $generation_url);die;
        return $path;
    }
}