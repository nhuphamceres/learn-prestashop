<?php
/* Smarty version 3.1.39, created on 2022-02-09 15:19:23
  from '/home/vagrant/prestashop-learn/admin/themes/default/template/content.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_6203790b337766_78888944',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'aab1a433aec9da69670a89d86d6ea88e0a5fd074' => 
    array (
      0 => '/home/vagrant/prestashop-learn/admin/themes/default/template/content.tpl',
      1 => 1643074756,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6203790b337766_78888944 (Smarty_Internal_Template $_smarty_tpl) {
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
