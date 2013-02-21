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
            if(isset($_SERVER['REDIRECT_URL']) AND (strrpos($output, $_SERVER['REDIRECT_URL']) === false))
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
        $code .= '$output=(isset($_SERVER["REDIRECT_URL"])?str_replace($_SERVER["REDIRECT_URL"],"#@@plxpREDIRECT_URL@@#",$output):$output);';

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
        $code .= '$output=(isset($_SERVER["REDIRECT_URL"])?str_replace("#@@plxpREDIRECT_URL@@#",$_SERVER["REDIRECT_URL"],$output):$output);';
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
            /**
             * Crée les RewriteRule
             * @param   $rule       url réécrite
             * @param   $motifs     tableau des expression correspondant aux jockers ($1, $2...)
             * @param   $original   url non réécrite
             * @return RewriteRule
             **/
            function genRewriteRule($rule, $motifs, $original) {
                if(!$rule)
                    return '';
                $rule = str_replace('.',"\\.",$rule);

                # Cas où $1 et $2 inversés
                if(count($motifs) == 2 and strpos($rule, '$1') > strpos($rule, '$2')) {
                    $original = str_replace('$A','$2',$original);
                    $original = str_replace('$B','$1',$original);
                }
                # Rétablie l'ordre des $1, $2, $3
                else if(count($motifs) == 3) {
                    echo $original;
                    $t = array('$A'=>strpos($rule, '$1'), '$B'=>strpos($rule, '$2'), '$C'=>strpos($rule, '$3'));
                    asort($t, SORT_NUMERIC);
                    $i = 1;
                    foreach($t as $k => $v) {
                        $original = str_replace($k,'$'.$i,$original);
                        $i++;
                    }
                }
                else {
                    $original = str_replace('$A','$1',$original);
                    $original = str_replace('$B','$2',$original);
                }

                foreach($motifs as $k => $m) {
                    $rule = str_replace('$'.($k+1),$m,$rule);
                }
                return 'RewriteRule ^' . $rule . '$ '.$original." \n";
            }
            # Génération de la configuration :
            # Nb. l'ordre a une importance
            $config = "# BEGIN -- plxPermalinks\n";
            $config .= genRewriteRule($this->getParam('pagescat_rule'), array('([0-9]+)', '([0-9a-z-]+)', '([0-9a-z-]+)'), 'index.php?categorie$A/$B/page$C/ [L]');
            $config .= genRewriteRule($this->getParam('pagesarchYM_rule'), array('([0-9]{4})', '([0-9]{2})', '([0-9]+)'), 'index.php?archives/$A/$B/page$C/ [L]');
            $config .= genRewriteRule($this->getParam('pagesarchY_rule'), array('([0-9]{4})', '([0-9]{4})', '([0-9]+)'), 'index.php?archives/$A/page$B/ [L]');
            $config .= genRewriteRule($this->getParam('pagestags_rule'), array('([a-z0-9-]+)', '([0-9]+)'), 'index.php?tag/$A/page$B/ [L]');
            $config .= genRewriteRule($this->getParam('art_rule'), array('([0-9]+)', '([0-9a-z-]*)'), 'index.php?article$A/$B [L]');
            $config .= genRewriteRule($this->getParam('static_rule'), array('([0-9]+)', '([0-9a-z-]+)'), 'index.php?static$A/$B [L,QSA]');
            $config .= genRewriteRule($this->getParam('cat_rule'), array('([0-9]+)', '([0-9a-z-]+)'), 'index.php?categorie$A/$B [L]');
            $config .= genRewriteRule($this->getParam('archYM_rule'), array('([0-9]{4})', '([0-9]{2})'), 'index.php?archives/$A/$B [L]');
            $config .= genRewriteRule($this->getParam('archY_rule'), array('([0-9]{4})'), 'index.php?archives/$A [L]');
            $config .= genRewriteRule($this->getParam('tags_rule'), array('([0-9a-z-]+)'), 'index.php?tag/$A [L]');
            $config .= genRewriteRule($this->getParam('pagesimple_rule'), array('([0-9]+)'), 'index.php?page$A [L]');
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
