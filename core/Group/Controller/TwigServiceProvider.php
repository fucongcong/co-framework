<?php

namespace Group\Controller;

use ServiceProvider;
use Group\Controller\WebExtension;

class TwigServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return object
     */
    public function register()
    {   
        $this->app->singleton('twig', function(){
            $path = \Config::get('view::path');
            $loader = new \Twig_Loader_Filesystem(__ROOT__.$path);

            if (\Config::get('view::cache')) {
                $cacheDir = \Config::get('view::cacheDir');
                $env = array(
                    'cache' => __ROOT__.$cacheDir
                );
            }

            $twig = new \Twig_Environment($loader, isset($env) ? $env : array());
            
            $twig->addExtension(new WebExtension());
            $extensions = \Config::get('view::extensions');
            foreach ($extensions as $extension) {
                $twig->addExtension(new $extension);
            }

            return $twig;
        });
    }

    public function getName()
    {
        return 'twig';
    }

}
