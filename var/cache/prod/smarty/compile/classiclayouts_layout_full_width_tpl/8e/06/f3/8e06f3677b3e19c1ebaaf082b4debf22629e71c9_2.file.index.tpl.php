<?php
/* Smarty version 3.1.39, created on 2022-02-14 09:23:00
  from '/home/vagrant/prestashop-learn/themes/classic/templates/index.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_6209bd040f9823_79594354',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8e06f3677b3e19c1ebaaf082b4debf22629e71c9' => 
    array (
      0 => '/home/vagrant/prestashop-learn/themes/classic/templates/index.tpl',
      1 => 1643074756,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6209bd040f9823_79594354 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
?>


    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_19755571086209bd040e66e1_46150631', 'page_content_container');
?>

<?php $_smarty_tpl->inheritance->endChild($_smarty_tpl, 'page.tpl');
}
/* {block 'page_content_top'} */
class Block_14364635236209bd040e8db0_76345391 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block 'page_content_top'} */
/* {block 'hook_home'} */
class Block_11305876346209bd040f0f75_69824600 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

            <?php echo $_smarty_tpl->tpl_vars['HOOK_HOME']->value;?>

          <?php
}
}
/* {/block 'hook_home'} */
/* {block 'page_content'} */
class Block_16347560386209bd040ed945_42524264 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

          <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_11305876346209bd040f0f75_69824600', 'hook_home', $this->tplIndex);
?>

        <?php
}
}
/* {/block 'page_content'} */
/* {block 'page_content_container'} */
class Block_19755571086209bd040e66e1_46150631 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'page_content_container' => 
  array (
    0 => 'Block_19755571086209bd040e66e1_46150631',
  ),
  'page_content_top' => 
  array (
    0 => 'Block_14364635236209bd040e8db0_76345391',
  ),
  'page_content' => 
  array (
    0 => 'Block_16347560386209bd040ed945_42524264',
  ),
  'hook_home' => 
  array (
    0 => 'Block_11305876346209bd040f0f75_69824600',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <section id="content" class="page-home">
        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_14364635236209bd040e8db0_76345391', 'page_content_top', $this->tplIndex);
?>


        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_16347560386209bd040ed945_42524264', 'page_content', $this->tplIndex);
?>

      </section>
    <?php
}
}
/* {/block 'page_content_container'} */
}
