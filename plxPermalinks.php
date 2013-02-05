<?php
/**
 * Plugin pour configurer finement les urls de PluXml
 * @author Amaury Graillat
 **/
class plxPermalinks extends plxPlugin {

    private $URLS_PATTERNS = array(
        "categorie([0-9]+)/([0-9a-z-]+)/page([0-9]+)",
        "archives/([0-9]{4})/([0-9]{2})/page([0-9]+)",
        "archives/([0-9]{4})/page([0-9]+)",
        "page([0-9a-z-]+)",
        "tag/([0-9a-z-]+)/page([0-9]+)",
        "article([0-9]+)/([a-z0-9-]*)",
        "static([0-9]+)/([a-z0-9-]*)",
        "categorie([0-9]+)/([a-z0-9-]*)",
        "tag/([0-9a-z-]+)",
        "archives/([0-9]{4})/([0-9]{2})",
        "archives/([0-9]{4})",
    );

    /**
     * Constructeur de la classe
     * @param string $default_lang langue par defaut.
     **/
    public function __construct($default_lang) {
        parent::__construct($default_lang);

        $this->setConfigProfil(PROFIL_ADMIN);

        $this->addHook('AdminTopBottom', 'AdminTopBottom');

        # Hook de mise à jour :
        $this->addHook('IndexEnd', 'IndexEnd');

        $this->addHook('plxFeedDemarrageBegin', 'bufferisation');
        $this->addHook('plxFeedDemarrageEnd', 'affichageBuffer');

        # Sitemap :
        $this->addHook('plxMotorDemarrageEnd', 'bufferisation');
        $this->addHook('SitemapArticles', 'affichageBufferSitemap');
    }

    /**
     * Méthode qui affiche un message si l'url rewriting n'est pas activée
     **/
    public function AdminTopBottom() {

        $string = '
            if(!$plxAdmin->aConf["urlrewriting"]) {
                echo "<p class=\"warning\">Plugin plxPermalinks<br />'.$this->getLang("ERROR_REWRITE").'</p>";
                plxMsg::Display();
    }';
    echo '<?php '.$string.' ?>';

    }

    /**
     * A l'activation du plugin
     **/
    public function onActivate() {
        $plxAdmin=plxAdmin::getInstance();
        # Si la réécriture d'url est activée on mets à jour le fichier .htaccess
        if($plxAdmin->aConf['urlrewriting'])
            $this->edithtaccess(true);
    }

    /**
     * A la désactivation du plugin
     **/
    public function onDeactivate() {
        $this->edithtaccess(false);
    }

    /**
     * Réécriture des url
     **/
    public function IndexEnd() {
        global $plxMotor;
        echo '<?php '.$this->getParam('code') . ' ?>';
        
        // Check if a redirection is needed
        if(isset($_SERVER['REDIRECT_QUERY_STRING']))
        {
            $output = $plxMotor->racine.$_SERVER['REDIRECT_QUERY_STRING'];
            eval($this->getParam('code'));
            if(strrpos($output, $_SERVER['REDIRECT_URL']) === false)
            {
                // 301 redirect for un-rewrited urls
                foreach($this->URLS_PATTERNS as $p)
                {
                    if(preg_match('#'.$p.'$#', $_SERVER['REDIRECT_QUERY_STRING']))
                    {
                        header('Status: 301 Moved Permanently', false, 301);
                        header('Location: '.$output);
                        exit();
                    }
                }

                // 303 error for others urls
                $plxMotor->error404();
            }
        }
    }

    /**
     * Réécriture dans sitemap.php et les flux
     **/
    public function bufferisation() {
        echo '<?php ob_start(); ?>';
    }
    public function affichageBuffer() {
        if(substr($_SERVER['REQUEST_URI'], strlen($_SERVER['REQUEST_URI'])-12) != "/sitemap.php")
        {
            echo '<?php $output = ob_get_clean(); ';
            echo $this->getParam('code');
            echo ' echo $output; ?>';
        }
    }
    public function affichageBufferSitemap() {
        echo '<?php $output = ob_get_clean();';
        echo $this->getParam('code');
        echo ' echo $output; ?>';
    }

    /**
     * Permet de générer le code à exécuter
     **/
    public function generateCode() {
        $plxAdmin = plxAdmin::getInstance();
        $code = '';

        # Renomme l'url courant déjà réécrite pour éviter qu'elle ne soit réécrite une seconde fois
        $code .= '$output=str_replace($_SERVER["REDIRECT_URL"],"#@@plxpREDIRECT_URL@@#",$output);';

        # Évite des problèmes dues à la pagination automatique
        if($this->getParam('pagescat_rule') or $this->getParam('pagesarchYM_rule') or $this->getParam('pagesarchY_rule') or $this->getParam('pagestags_rule'))
            $code .= '$output=preg_replace("#('.$plxAdmin->aConf["racine"].'[0-9a-z-/]*page)[0-9]+//page([0-9]+)#","$1$2",$output);';

        # Nb. L'ordre a une importance.
        # Nb. Le '@' sert rend l'url modifiable par une seule règle
        if($this->getParam('pagescat_rule'))
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'categorie([0-9]+)/([0-9a-z-]+)/page([0-9]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('pagescat_rule') . '",$output);';

        # Pages dans les archives
        if($this->getParam('pagesarchYM_rule')) # Année-mois
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'archives/([0-9]{4})/([0-9]{2})/page([0-9]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('pagesarchYM_rule') . '",$output);';
        if($this->getParam('pagesarchY_rule')) # Année
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'archives/([0-9]{4})/page([0-9]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('pagesarchY_rule') . '",$output);';

        if($this->getParam('pagesimple_rule')) {
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'page([0-9a-z-]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('pagesimple_rule') . '",$output);';
        }

        if($this->getParam('pagestags_rule')) {
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'tag/([0-9a-z-]+)/page([0-9]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('pagestags_rule') . '",$output);';
        }

        if($this->getParam('art_rule'))
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'article([0-9]+)/([a-z0-9-]*)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('art_rule') . '",$output);';
        if($this->getParam('static_rule'))
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'static([0-9]+)/([a-z0-9-]*)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('static_rule') . '",$output);';

        if($this->getParam('cat_rule'))
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'categorie([0-9]+)/([a-z0-9-]*)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('cat_rule') . '",$output);';
        if($this->getParam('tags_rule'))
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'tag/([0-9a-z-]+)#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('tags_rule') . '",$output);';

        # Archive :
        if($this->getParam('archYM_rule')) # Année-mois
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'archives/([0-9]{4})/([0-9]{2})#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('archYM_rule') . '",$output);';
        if($this->getParam('archY_rule')) # Année
            $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'archives/([0-9]{4})#","'.$plxAdmin->aConf["racine"].'@'.$this->getParam('archY_rule') . '",$output);';

        $code .= '$output=preg_replace("#'.$plxAdmin->aConf["racine"].'@#","'.$plxAdmin->aConf["racine"].'",$output);';
        $code .= '$output=str_replace("#@@plxpREDIRECT_URL@@#",$_SERVER["REDIRECT_URL"],$output);';
        return $code;
    }


    /**
     * Permet de modifier le .htaccess
     * @param   object instance plxPlugin
     * @return  faux s'il y a eu un problème.
     **/
    public function edithtaccess($activate) {

        if(!file_exists(PLX_ROOT.'.htaccess'))
            return false;

        $htaccess = file_get_contents(PLX_ROOT.'.htaccess');

        if(!strstr($htaccess, '# END -- Pluxml'))
            return false;

        # Si c'est le premier enregistrement : génération des balises
        if(!strstr($htaccess, 'BEGIN -- plxPermalinks')) {
            $htaccess = str_replace('RewriteCond %{REQUEST_FILENAME} !-f', "# BEGIN -- plxPermalinks\n# END -- plxPermalinks\nRewriteCond %{REQUEST_FILENAME} !-f", $htaccess);
        }

        $config='';
        if($activate) {
            # Génération de la configuration :
            # Nb. l'ordre a une importance
            $config = "# BEGIN -- plxPermalinks\n";
            if($rule = $this->getParam('pagescat_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]+)',$rule);
                $rule = str_replace('$2','([0-9a-z-]+)',$rule);
                $rule = str_replace('$3','([0-9a-z-]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?categorie$1/$2/page$3/ [L]'."\n";
            }
            if($rule = $this->getParam('pagesarchYM_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]{4})',$rule);
                $rule = str_replace('$2','([0-9]{2})',$rule);
                $rule = str_replace('$3','([0-9]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?archives/$1/$2/page$3/ [L]'."\n";
            }
            if($rule = $this->getParam('pagesarchY_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]{4})',$rule);
                $rule = str_replace('$2','([0-9]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?archives/$1/page$2/ [L]'."\n";
            }
            if($rule = $this->getParam('pagestags_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([a-z0-9-]+)',$rule);
                $rule = str_replace('$2','([0-9]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?tag/$1/page$2/ [L]'."\n";
            }
            if($rule = $this->getParam('art_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]+)',$rule);
                $rule = str_replace('$2','([0-9a-z-]*)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?article$1/$2 [L]'."\n";
            }
            if($rule = $this->getParam('static_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]+)',$rule);
                $rule = str_replace('$2','([0-9a-z-]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?static$1/$2 [L,QSA]'."\n";
            }
            if($rule = $this->getParam('cat_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]+)',$rule);
                $rule = str_replace('$2','([0-9a-z-]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?categorie$1/$2 [L]'."\n";
            }
            if($rule = $this->getParam('archYM_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]{4})',$rule);
                $rule = str_replace('$2','([0-9]{2})',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?archives/$1/$2 [L]'."\n";
            }
            if($rule = $this->getParam('archY_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]{4})',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?archives/$1 [L]'."\n";
            }
            if($rule = $this->getParam('tags_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9a-z-]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?tag/$1 [L]'."\n";
            }
            if($rule = $this->getParam('pagesimple_rule')) {
                $rule = str_replace('.',"\\.",$rule);
                $rule = str_replace('$1','([0-9]+)',$rule);
                $config .= 'RewriteRule ^' . $rule . '$ index.php?page$1 [L]'."\n";
            }
            $config .= "# END -- plxPermalinks\n";
        }

        $htaccess = preg_replace("/# BEGIN -- plxPermalinks(.*)# END -- plxPermalinks\n/s", '# BEGIN -- plxPermalinks', $htaccess);
        $htaccess = str_replace('# BEGIN -- plxPermalinks', $config, $htaccess);

        if(!file_put_contents(PLX_ROOT.'.htaccess', $htaccess)) {
            return plxMsg::Error(ERROR_ACCESS);
        }

        return true;
    }
}
?>