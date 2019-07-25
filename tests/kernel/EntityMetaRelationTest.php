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

    $entity_relation_manager = \Drupal::service('emr.manager');

    // Create node.
    $node = Node::create([
      'type' => 'entity_meta_example',
      'title' => 'Node test',
    ]);
    $node->save();

    // Asserts that node title was correctly saved.
    $node_new = Node::load($node->id());
    $this->assertEqual($node_new->label(), 'Node test');

    // Asserts that node has no relations.
    $entity_meta_relations = $entity_relation_manager->getRelatedEntityMeta($node_new->getRevisionId());
    $this->assertEqual($entity_meta_relations, []);

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

    // Assert that node has relations.
    $related_entity_meta_pre_save_ids = $related_entity_meta_post_save_ids = $related_entity_meta_final_save_ids = [];
    $related_entity_meta = $entity_relation_manager->getRelatedEntityMeta($node_new->getRevisionId());
    $this->assertNotEmpty($related_entity_meta);

    array_walk($related_entity_meta, function ($entity_meta) use (&$related_entity_meta_pre_save_ids) {
      $related_entity_meta_pre_save_ids[] += $entity_meta->getRevisionId();
    });

    // Save node alone, should copy the relationships.
    $node_new->set('title', 'Node test updated');
    $node_new->save();

    // Assert new node revision keeps having relationships.
    $related_entity_meta = $entity_relation_manager->getRelatedEntityMeta($node_new->getRevisionId());
    $this->assertNotEqual($related_entity_meta, []);

    array_walk($related_entity_meta, function ($entity_meta) use (&$related_entity_meta_post_save_ids) {
      $related_entity_meta_post_save_ids[] += $entity_meta->getRevisionId();
    });

    $this->assertEquals($related_entity_meta_post_save_ids, $related_entity_meta_pre_save_ids);

    // Save entity meta alone.
    $meta_entity_new->set('field_color', 'green');
    $meta_entity_new->save();

    // Assert node has updated relationships to new entity meta revisions.
    $related_entity_meta = $entity_relation_manager->getRelatedEntityMeta($node_new->getRevisionId());
    array_walk($related_entity_meta, function ($entity_meta) use (&$related_entity_meta_final_save_ids) {
      $related_entity_meta_final_save_ids[] += $entity_meta->getRevisionId();
    });

    $this->assertNotEmpty($related_entity_meta);
    $this->assertEmpty(array_diff(array_values($related_entity_meta_post_save_ids), array_values($related_entity_meta_final_save_ids)));
  }

}
