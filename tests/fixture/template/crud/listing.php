<?php $this->extend('theme.php') ?>

<?php $this->block('content') ?>
<table>
  <thead>
    <tr>
      <?php foreach ($crud->fields as $field => $def): ?>
        <th><?= $def['label'] ?></th>
      <?php endforeach ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($crud->data['subset'] as $item): ?>
      <tr>
        <?php foreach ($crud->fields as $field => $def): ?>
          <td><?= $item[$field] ?></td>
        <?php endforeach ?>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<?php $this->endBlock() ?>