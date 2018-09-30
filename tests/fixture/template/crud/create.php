<?php $this->extend('theme.php') ?>

<?php $this->block('content') ?>
<?= $crud->form->render() ?>
<?php $this->endBlock() ?>