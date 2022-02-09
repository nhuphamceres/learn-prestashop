<?php
/* Smarty version 3.1.39, created on 2022-02-09 15:16:01
  from '/home/vagrant/prestashop-learn/admin638kbyy1o/themes/default/template/content.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62037841491392_32071920',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '48d714c05412d88f4542004668ca3fa32a3640a8' => 
    array (
      0 => '/home/vagrant/prestashop-learn/admin638kbyy1o/themes/default/template/content.tpl',
      1 => 1643074756,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62037841491392_32071920 (Smarty_Internal_Template $_smarty_tpl) {
?><div id="ajax_confirmation" class="alert alert-success hide"></div>
<div id="ajaxBox" style="display:none"></div>

<div class="row">
	<div class="col-lg-12">
		<?php if ((isset($_smarty_tpl->tpl_vars['content']->value))) {?>
			<?php echo $_smarty_tpl->tpl_vars['content']->value;?>

		<?php }?>
	</div>
</div>
<?php }
}
