<?php $this->extend('layout') ?>

<?php $this->start('content') ?>
Home link: <?= $crud->path() ?>

Create link: <?= $crud->path('create', 'action=create') ?>

<table>
  <thead>
    <tr>
      <?php foreach ($crud->fields as $field): ?>
        <th><?= $field['label'] ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($crud->data['mapper'] as $mapper): ?>
      <tr>
        <?php foreach ($crud->fields as $field): ?>
          <td><?= $mapper[$field['name']] ?></td>
        <?php endforeach ?>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php $this->stop() ?>