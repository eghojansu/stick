<?php $this->layout('layout', ['var'=>$var]) ?>

<?php $this->block('title') ?>Main page - <?=$this->parent()?><?php $this->endBlock() ?>

<?php $this->block('body') ?>
  Main page content <?=$this->e($var)?>

  <?=$this->filter($var, 'upper|lcfirst')?>

  <?=$this->esc($var, 'upper')?>

  <?=$this->e($var, 'ucfirst')?>

  <?=var_export($this->startswith('<span>', $var), true)?>

  <?=var_export($this->filter($var, 'startswith:"<span>"'), true)?>
<?php $this->endBlock() ?>

<?php $this->block('js') ?>
  <?=$this->parent()?>
  <script src="main.js">
<?php $this->endBlock() ?>
