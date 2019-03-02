<table>
  <tbody>
    <?php foreach ($crud->fields as $field): ?>
      <tr>
        <th><?= $field['label'] ?></th>
        <td><?= $crud->mapper[$field['name']] ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>