<?php if(!defined('PLX_ROOT')) exit; ?>
<?php

# Control du token du formulaire
plxToken::validateFormToken($_POST);

if(!empty($_POST['save'])) {

	if(!$plxAdmin->aConf['urlrewriting']) {
		plxMsg::Error($plxPlugin->getLang('ERROR_REWRITE'));
		# Redirection sur la page de config du plugin
		header('Location: parametres_plugin.php?p=plxPermalinks');
		exit;
	}

	# Enregistrement des paramètres
	$plxPlugin->setParam('art_rule', $_POST['art_rule'], 'string');
	$plxPlugin->setParam('static_rule', $_POST['static_rule'], 'string');
	$plxPlugin->setParam('tags_rule', $_POST['tags_rule'], 'string');
	$plxPlugin->setParam('pagestags_rule', $_POST['pagestags_rule'], 'string');
	$plxPlugin->setParam('pagesimple_rule', $_POST['pagesimple_rule'], 'string');
	$plxPlugin->setParam('cat_rule', $_POST['cat_rule'], 'string');
	$plxPlugin->setParam('pagescat_rule', $_POST['pagescat_rule'], 'string');
	$plxPlugin->setParam('archYM_rule', $_POST['archYM_rule'], 'string');
	$plxPlugin->setParam('archY_rule', $_POST['archY_rule'], 'string');
	$plxPlugin->setParam('pagesarchYM_rule', $_POST['pagesarchYM_rule'], 'string');
	$plxPlugin->setParam('pagesarchY_rule', $_POST['pagesarchY_rule'], 'string');

	# Génération et enregistrement du code
	$plxPlugin->setParam('code', $plxPlugin->generateCode(), 'string');
	$plxPlugin->saveParams();

	# Si la réécriture d'url est activée on mets à jour le fichier .htaccess
	if($plxAdmin->aConf['urlrewriting'])
		$plxPlugin->edithtaccess(true);

	# Redirection sur la page de config du plugin
	header('Location: parametres_plugin.php?p=plxPermalinks');
	exit;
}
?>

<script type="text/javascript">
function restore_permalinks() {
	document.getElementById('id_art_rule').value='article/$1/$2.html';
	document.getElementById('id_static_rule').value='static/$1/$2.html';
	document.getElementById('id_tags_rule').value='tag/$1.html';
	document.getElementById('id_pagesimple_rule').value='page$1.html';
	document.getElementById('id_cat_rule').value='category/$1/$2.html';
	document.getElementById('id_pagescat_rule').value='category/$1/$2/page/$3.html';
	document.getElementById('id_archYM_rule').value='archives/$1/$2.html';
	document.getElementById('id_archY_rule').value='archives/$1.html';
	document.getElementById('id_pagesarchYM_rule').value='archives/$1/$2/page/$3.html';
	document.getElementById('id_pagesarchY_rule').value='archives/$1/page/$2.html';
	document.getElementById('id_pagestags_rule').value='tag/$1/$2.html';
}
</script>

<h2><?php echo $plxPlugin->getInfo('title') ?></h2>

<form action="parametres_plugin.php?p=plxPermalinks" method="post">
	<fieldset class="withlabel">
		<legend><strong><?php $plxPlugin->lang('LEGEND_ARTS') ?></strong></legend>
		<p style="padding-top:10px;">
			<?php $plxPlugin->lang('USERGUIDE2') ?>
		</p>
		<p class="field"><label for="id_art_rule"><?php $plxPlugin->lang('L_ARTICLES') ?></label></p>
		<?php plxUtils::printInput('art_rule',$plxPlugin->getParam('art_rule'),'text','30-500') ?> $1 : <?php $plxPlugin->lang('L_DEF_NUMBER') ?>, $2 : <?php $plxPlugin->lang('L_DEF_NAME') ?>
		<p class="field"><label for="id_pagesimple_rule"><?php $plxPlugin->lang('L_PAGES') ?></label></p>
		<?php plxUtils::printInput('pagesimple_rule',$plxPlugin->getParam('pagesimple_rule'),'text','30-500') ?> $1 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
	</fieldset>

	<fieldset class="withlabel">
		<legend><strong><?php $plxPlugin->lang('LEGEND_STATICS') ?></strong></legend>
		<p class="field"><label for="id_static_rule"><?php $plxPlugin->lang('L_STATICS') ?></label></p>
		<?php plxUtils::printInput('static_rule',$plxPlugin->getParam('static_rule'),'text','30-500') ?> $1 : <?php $plxPlugin->lang('L_DEF_NUMBER') ?>, $2 : <?php $plxPlugin->lang('L_DEF_NAME') ?>
	</fieldset>

	<fieldset class="withlabel">
		<legend><strong><?php $plxPlugin->lang('LEGEND_CATS') ?></strong></legend>
		<p class="field"><label for="id_cat_rule"><?php $plxPlugin->lang('L_CATS') ?></label></p>
		<?php plxUtils::printInput('cat_rule',$plxPlugin->getParam('cat_rule'),'text','30-500') ?>  $1 : <?php $plxPlugin->lang('L_DEF_NUMBER') ?>, $2 : <?php $plxPlugin->lang('L_DEF_NAME') ?>
		<p class="field"><label for="id_pagescat_rule"><?php $plxPlugin->lang('L_PAGESCATS') ?></label></p>
		<?php plxUtils::printInput('pagescat_rule',$plxPlugin->getParam('pagescat_rule'),'text','30-500') ?>  $1 : <?php $plxPlugin->lang('L_DEF_NUMBER') ?>, $2 : <?php $plxPlugin->lang('L_DEF_NAME') ?>, $3 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
	</fieldset>

	<fieldset class="withlabel">
		<legend><strong><?php $plxPlugin->lang('LEGEND_TAGS') ?></strong></legend>
		<p class="field"><label for="id_tags_rule"><?php $plxPlugin->lang('L_TAGS') ?></label></p>
		<?php plxUtils::printInput('tags_rule',$plxPlugin->getParam('tags_rule'),'text','30-500') ?> $1 : <?php $plxPlugin->lang('L_DEF_NAME') ?>

		<p class="field"><label for="id_pagestags_rule"><?php $plxPlugin->lang('L_PAGESTAGS') ?></label></p>
		<?php plxUtils::printInput('pagestags_rule',$plxPlugin->getParam('pagestags_rule'),'text','30-500') ?> $1 : <?php $plxPlugin->lang('L_DEF_NAME') ?>, $2 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
	</fieldset>

	<fieldset class="withlabel">
		<legend><strong><?php $plxPlugin->lang('LEGEND_ARCHIVES') ?></strong></legend>

		<p class="field"><label for="id_archYM_rule"><?php $plxPlugin->lang('L_ARCHS_YM') ?></label></p>
		<?php plxUtils::printInput('archYM_rule',$plxPlugin->getParam('archYM_rule'),'text','30-500') ?>  $1 : <?php $plxPlugin->lang('L_DEF_YEAR') ?>, $2 : <?php $plxPlugin->lang('L_DEF_MONTH') ?>, $3 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
		<p class="field"><label for="id_archY_rule"><?php $plxPlugin->lang('L_ARCHS_Y') ?></label></p>
		<?php plxUtils::printInput('archY_rule',$plxPlugin->getParam('archY_rule'),'text','30-500') ?>  $1 : <?php $plxPlugin->lang('L_DEF_YEAR') ?>

		<p class="field"><label for="id_pagesarchYM_rule"><?php $plxPlugin->lang('L_PAGESARCHS_YM') ?></label></p>
		<?php plxUtils::printInput('pagesarchYM_rule',$plxPlugin->getParam('pagesarchYM_rule'),'text','30-500') ?>   $1 : <?php $plxPlugin->lang('L_DEF_YEAR') ?>, $2 : <?php $plxPlugin->lang('L_DEF_MONTH') ?>, $3 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
		<p class="field"><label for="id_pagesarchY_rule"><?php $plxPlugin->lang('L_PAGESARCHS_Y') ?></label></p>
		<?php plxUtils::printInput('pagesarchY_rule',$plxPlugin->getParam('pagesarchY_rule'),'text','30-500') ?>   $1 : <?php $plxPlugin->lang('L_DEF_YEAR') ?>, $2 : <?php $plxPlugin->lang('L_DEF_PAGE_NUMBER') ?>
	</fieldset>
	<p style="padding-top:10px;">
		<?php $plxPlugin->lang('USERGUIDE') ?>
	</p>
	<p style="padding-top:10px;">
		<?php echo plxToken::getTokenPostMethod() ?>
		<input type="submit" name="save" value="<?php $plxPlugin->lang('SAVE') ?>" />
		&nbsp;<a href="javascript:void(0)" onclick="restore_permalinks()"><?php $plxPlugin->lang('RESTORE') ?></a>
	</p>
</form>
