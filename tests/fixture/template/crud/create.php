<?php $this->extend('theme.php') ?>

<?php $this->block('content') ?>
<a href="<?= $this->crudLink() ?>">Back</a>
<?= $crud['form']->render() ?>
<?php $this->endBlock() ?>