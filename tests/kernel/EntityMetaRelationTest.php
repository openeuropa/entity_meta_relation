<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the entity meta and entity meta relation entities are handled.
 *
 * @todo test the content entity deletes the metas
 * @todo test no relation duplicates are created
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
   * Tests that entity meta can be correctly related to content entities (node).
   */
  public function testEntityMetaRelations() {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_meta_storage */
    $entity_meta_relation_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    /** @var \Drupal\node\NodeInterface $first_node */
    $first_node = $node_storage->create([
      'type' => 'entity_meta_example',
      'title' => 'Node test',
    ]);
    $first_node->save();
    $this->assertEquals(1, $first_node->getRevisionId());

    $second_node = $node_storage->create([
      'type' => 'entity_meta_example',
      'title' => 'Node test',
    ]);
    $second_node->save();
    $this->assertEquals(2, $second_node->getRevisionId());

    // Asserts that node has no relations.
    $entity_meta_relations = $entity_meta_storage->getRelatedMetaEntities($second_node);
    $this->assertEmpty($entity_meta_relations);

    // Create entity meta for bundle "visual".
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta = $entity_meta_storage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);
    $entity_meta->save();

    // Asserts that color was correctly saved.
    $this->assertEqual($entity_meta->get('field_color')->value, 'red');
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // No host entity was specified so there should be no relations between the
    // the node and the entity meta.
    $entity_meta_relation_storage->resetCache();
    $this->assertEmpty($entity_meta_relation_storage->loadMultiple());

    // Set a host entity onto the entity meta and re-save to make a relationship
    // between the two.
    $entity_meta->setHostEntity($second_node);
    $entity_meta->save();

    // No entity meta values have been changed so no new revision should have
    // been created.
    $entity_meta_storage->resetCache();
    $entity_meta = $entity_meta_storage->load($entity_meta->id());
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // A relationship between the node and the entity meta should have been
    // created.
    $entity_meta_relations = $entity_meta_relation_storage->loadMultiple();
    $this->assertCount(1, $entity_meta_relations);
    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface $entity_meta_relation */
    $entity_meta_relation = reset($entity_meta_relations);
    $this->assertEquals(1, $entity_meta_relation->getRevisionId());

    // Assert relationship between the two entities is present in the loaded
    // entity meta relation entity.
    $this->assertEqual(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEqual(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEqual(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEqual(2, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $entity_meta_storage->getRelatedMetaEntities($second_node);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta_entities[1]->getRevisionId());

    // Update the node alone and check that the relation was updated to point
    // to the new node revision.
    $second_node->set('title', 'Node test updated');
    $second_node->setNewRevision(TRUE);
    $second_node->save();
    $node_storage->resetCache();
    $node_new = $node_storage->loadRevision($node_storage->getLatestRevisionId($second_node->id()));
    $this->assertEquals(3, $node_new->getRevisionId());
    // Only one entity meta relation entities should still exist.
    $entity_meta_relation_storage->resetCache();
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta_relation = $entity_meta_relation_storage->load(1);
    $this->assertEqual(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEqual(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEqual(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEqual(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $entity_meta_storage->getRelatedMetaEntities($node_new);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta_entities[1]->getRevisionId());

    // Update only the entity meta and check that the revision gets updated.
    $entity_meta->set('field_color', 'green');
    $entity_meta->save();
    $node_storage->resetCache();
    $node_new = $node_storage->loadRevision($node_storage->getLatestRevisionId($second_node->id()));
    // No change in the node itself.
    $this->assertEquals(3, $node_new->getRevisionId());
    $entity_meta_relation_storage->resetCache();
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta_relation = $entity_meta_relation_storage->load(1);
    $this->assertEqual(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEqual(2, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEqual(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEqual(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $entity_meta_storage->getRelatedMetaEntities($node_new);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals(2, $related_entity_meta_entities[1]->getRevisionId());

    // Check that we can retrieve the related content entities of a meta entity.
    $nodes = $entity_meta_storage->getRelatedContentEntities($entity_meta, 'node');
    $this->assertCount(1, $nodes);
    $node = reset($nodes);
    // We used the latest entity meta to retrieve the related nodes, so the
    // revision ID of the retrieved node should be the latest.
    $this->assertEqual(3, $node->getRevisionId());

    // Check that we can retrieve the related content entities of a meta entity
    // also using older revisions.
    $entity_meta = $entity_meta_storage->loadRevision(1);
    $nodes = $entity_meta_storage->getRelatedContentEntities($entity_meta, 'node');
    $this->assertCount(1, $nodes);
    $node = reset($nodes);
    $this->assertEqual(2, $node->getRevisionId());

    // Check that the storage method for finding related meta entities works
    // also with older revisions.
    $entity_meta_entities = $entity_meta_storage->getRelatedMetaEntities($node);
    $this->assertCount(1, $entity_meta_entities);
    $entity_meta = reset($entity_meta_entities);
    $this->assertEqual(1, $entity_meta->getRevisionId());

    // Check that if we delete a content entity which has meta relations, they
    // get deleted as well.
    $node->delete();
    $this->assertEmpty($entity_meta_storage->loadMultiple());
    $this->assertEmpty($entity_meta_relation_storage->loadMultiple());
  }

}
