<?php
/* Smarty version 3.1.39, created on 2022-02-09 14:54:37
  from '/home/vagrant/prestashop-learn/themes/classic/templates/index.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_6203733dcafc22_17397360',
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
function content_6203733dcafc22_17397360 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_loadInheritance();
$_smarty_tpl->inheritance->init($_smarty_tpl, true);
?>


    <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_5327593936203733dc97e25_82202076', 'page_content_container');
?>

<?php $_smarty_tpl->inheritance->endChild($_smarty_tpl, 'page.tpl');
}
/* {block 'page_content_top'} */
class Block_561889026203733dc9b6e1_11300723 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
}
}
/* {/block 'page_content_top'} */
/* {block 'hook_home'} */
class Block_18579749376203733dca37f5_55633660 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

            <?php echo $_smarty_tpl->tpl_vars['HOOK_HOME']->value;?>

          <?php
}
}
/* {/block 'hook_home'} */
/* {block 'page_content'} */
class Block_21296323036203733dca0611_77357435 extends Smarty_Internal_Block
{
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

          <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_18579749376203733dca37f5_55633660', 'hook_home', $this->tplIndex);
?>

        <?php
}
}
/* {/block 'page_content'} */
/* {block 'page_content_container'} */
class Block_5327593936203733dc97e25_82202076 extends Smarty_Internal_Block
{
public $subBlocks = array (
  'page_content_container' => 
  array (
    0 => 'Block_5327593936203733dc97e25_82202076',
  ),
  'page_content_top' => 
  array (
    0 => 'Block_561889026203733dc9b6e1_11300723',
  ),
  'page_content' => 
  array (
    0 => 'Block_21296323036203733dca0611_77357435',
  ),
  'hook_home' => 
  array (
    0 => 'Block_18579749376203733dca37f5_55633660',
  ),
);
public function callBlock(Smarty_Internal_Template $_smarty_tpl) {
?>

      <section id="content" class="page-home">
        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_561889026203733dc9b6e1_11300723', 'page_content_top', $this->tplIndex);
?>


        <?php 
$_smarty_tpl->inheritance->instanceBlock($_smarty_tpl, 'Block_21296323036203733dca0611_77357435', 'page_content', $this->tplIndex);
?>

      </section>
    <?php
}
}
/* {/block 'page_content_container'} */
}
