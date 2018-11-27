<?php $this->extend('crud/layout') ?>

<?php $this->start('content') ?>
<h1><?= $crud->title ?></h1>

<table>
  <thead>
    <tr>
      <?php foreach ($crud->fields as $field): ?>
      <th><?= $field['label'] ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($crud->data['subset'] as $item): ?>
      <tr>
        <?php foreach ($crud->fields as $field): ?>
        <td><?= $item[$field['name']] ?></td>
        <?php endforeach ?>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php $this->stop() ?>