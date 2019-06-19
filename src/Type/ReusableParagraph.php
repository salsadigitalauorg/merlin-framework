<?php

namespace Migrate\Type;

use Migrate\Command\GenerateCommand;
use Ramsey\Uuid\Uuid;

class ReusableParagraph extends TypeBase implements TypeInterface {

  public function process() {
    $name = $this->config['name'];
    $row = new \stdClass;
    $uuids = [];

    foreach ($this->config['children'] as $child) {
      // Generate a new Type so that we can process the row with the defined
      // types. This should update our $row instance locally, with which we
      // can add a uuid afterwards and then reference this UUID in the entity.
      $type = GenerateCommand::TypeFactory(
        $child['type'],
        $this->crawler,
        $this->output,
        $row,
        $child
      );

      try {
        $type->process();
      } catch (\Exception $e) {}
    }

    // Generate a UUID based on the selected row values.
    $tmp = md5(json_encode($row));
    $row->uuid = Uuid::uuid3(Uuid::NAMESPACE_DNS, $tmp);
    $uuids[] = $row->uuid;

    $this->output->mergeRow($name, 'data', [$row], TRUE);

    $this->row->{$name} = [
      'type' => $this->config['options']['type'],
      'paragraphs' => $uuids
    ];
  }

}
