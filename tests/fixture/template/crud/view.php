<?php $this->extend('crud/layout') ?>

<?php $this->start('content') ?>
<h1><?= $crud->title ?></h1>

<table>
  <tbody>
    <?php foreach ($crud->fields as $field): ?>
      <tr>
        <td><?= $field['label'] ?></td>
        <td><?= $crud->mapper[$field['name']] ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php $this->stop() ?>