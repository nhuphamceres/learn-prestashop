<?php
/* Smarty version 3.1.39, created on 2022-02-09 14:54:38
  from '/home/vagrant/prestashop-learn/themes/classic/templates/_partials/helpers.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_6203733e7defc7_32955582',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0eccf99861411b844dbd7a7b20c24aa938e6363e' => 
    array (
      0 => '/home/vagrant/prestashop-learn/themes/classic/templates/_partials/helpers.tpl',
      1 => 1643074756,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6203733e7defc7_32955582 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->smarty->ext->_tplFunction->registerTplFunctions($_smarty_tpl, array (
  'renderLogo' => 
  array (
    'compiled_filepath' => '/home/vagrant/prestashop-learn/var/cache/prod/smarty/compile/classiclayouts_layout_full_width_tpl/0e/cc/f9/0eccf99861411b844dbd7a7b20c24aa938e6363e_2.file.helpers.tpl.php',
    'uid' => '0eccf99861411b844dbd7a7b20c24aa938e6363e',
    'call_name' => 'smarty_template_function_renderLogo_12891385146203733e79c710_50487525',
  ),
));
?> 

<?php }
/* smarty_template_function_renderLogo_12891385146203733e79c710_50487525 */
if (!function_exists('smarty_template_function_renderLogo_12891385146203733e79c710_50487525')) {
function smarty_template_function_renderLogo_12891385146203733e79c710_50487525(Smarty_Internal_Template $_smarty_tpl,$params) {
foreach ($params as $key => $value) {
$_smarty_tpl->tpl_vars[$key] = new Smarty_Variable($value, $_smarty_tpl->isRenderingCache);
}
?>

  <a href="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['urls']->value['pages']['index'], ENT_QUOTES, 'UTF-8');?>
">
    <img
      class="logo img-fluid"
      src="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['shop']->value['logo_details']['src'], ENT_QUOTES, 'UTF-8');?>
"
      alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['shop']->value['name'], ENT_QUOTES, 'UTF-8');?>
"
      width="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['shop']->value['logo_details']['width'], ENT_QUOTES, 'UTF-8');?>
"
      height="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['shop']->value['logo_details']['height'], ENT_QUOTES, 'UTF-8');?>
">
  </a>
<?php
}}
/*/ smarty_template_function_renderLogo_12891385146203733e79c710_50487525 */
}
