<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\emr\Entity\EntityMeta;
use Drupal\emr\Entity\EntityMetaRelation;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests that all entity types and fields involved in emr are correctly set.
 */
class EntityMetaRelationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_meta_example',
    'emr',
    'emr_node',
    'node',
    'user',
    'menu_ui',
    'options',
    'entity_reference_revisions',
    'field',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_meta');
    $this->installEntitySchema('entity_meta_relation');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['emr', 'emr_node', 'entity_meta_example']);
  }

  /**
   * Tests using entity fields of the entity version field type.
   */
  public function testEntitySave() {

    // Create node.
    $node = Node::create([
      'type' => 'entity_meta_example',
      'title' => 'Node test',
    ]);
    $node->save();

    // Asserts that node title was correctly saved.
    $node_new = Node::load($node->id());
    $this->assertEqual($node_new->label(), 'Node test');

    // Create entity meta for bundle visual.
    $meta_entity = EntityMeta::create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);

    $meta_entity->save();

    // Asserts that color was correctly saved.
    $meta_entity_new = EntityMeta::load($meta_entity->id());
    $this->assertEqual($meta_entity_new->field_color->value, 'red');

    // Manually create relationship between the two.
    $entity_meta_relation = EntityMetaRelation::create([
      'bundle' => 'node_meta_relation',
      'emr_meta_revision' => $meta_entity_new,
      'emr_node_revision' => $node_new,
    ]);

    $entity_meta_relation->save();
    $entity_meta_relation_new = EntityMetaRelation::load($entity_meta_relation->id());

    // Assert relationship between the two entities is present.
    $this->assertEqual($entity_meta_relation_new->emr_meta_revision[0]->target_id, $meta_entity_new->getRevisionId());
    $this->assertEqual($entity_meta_relation_new->emr_node_revision[0]->target_id, $node_new->getRevisionId());
  }

}
