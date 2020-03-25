<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\emr\Field\EntityMetaItemListInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;

/**
 * Tests that the entity meta and entity meta relation entities are handled.
 */
class EntityMetaRelationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_meta_example',
    'entity_meta_audio',
    'entity_meta_visual',
    'entity_meta_speed',
    'entity_meta_force',
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
   * The entity meta storage.
   *
   * @var \Drupal\emr\EntityMetaStorageInterface
   */
  protected $entityMetaStorage;

  /**
   * The entity meta relation storage.
   *
   * @var \Drupal\emr\EntityMetaRelationStorageInterface
   */
  protected $entityMetaRelationStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_meta');
    $this->installEntitySchema('entity_meta_relation');
    $this->installSchema('node', 'node_access', 'emr_node');
    $this->installConfig(
      ['emr', 'emr_node', 'entity_meta_example',
        'entity_meta_audio', 'entity_meta_visual', 'entity_meta_speed', 'entity_meta_force',
      ]);

    $emr_installer = \Drupal::service('emr.installer');
    $emr_installer->installEntityMetaTypeOnContentEntityType('audio', 'node');
    $emr_installer->installEntityMetaTypeOnContentEntityType('speed', 'node');

    $this->entityMetaStorage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    $this->entityMetaRelationStorage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    // We create a first node we don't use for anything to ensure we don't
    // have coincidental matching IDs between nodes and entity metas.
    /** @var \Drupal\node\NodeInterface $first_node */
    $first_node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'First node',
    ]);
    $first_node->save();
    $this->assertEquals(1, $first_node->getRevisionId());
  }

  /**
   * Tests that entity meta can be correctly related to content entities (node).
   */
  public function testSingleEntityMetaRelations() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // Asserts that node has no relations.
    $entity_meta_relations = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertEmpty($entity_meta_relations);

    // Manually create an entity meta for bundle "visual".
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);
    $entity_meta->save();

    // Asserts that color was correctly saved.
    $this->assertEquals('red', $entity_meta->get('field_color')->value);
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // No host entity was specified so there should be no relations between the
    // the node and the entity meta.
    $this->entityMetaRelationStorage->resetCache();
    $this->assertEmpty($this->entityMetaRelationStorage->loadMultiple());

    // Set a host entity onto the entity meta and re-save to make a relationship
    // between the two.
    $entity_meta->setHostEntity($node);
    $entity_meta->save();

    // No entity meta values have been changed so no new revision should have
    // been created.
    $this->entityMetaStorage->resetCache();
    $results = $this->entityMetaStorage->getQuery()->allRevisions()->execute();
    $this->assertCount(1, $results);

    // However, a relationship between the node and the entity meta should have
    // been created.
    $entity_meta_relations = $this->entityMetaRelationStorage->loadMultiple();
    $this->assertCount(1, $entity_meta_relations);
    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface $entity_meta_relation */
    $entity_meta_relation = reset($entity_meta_relations);
    // Only one revision of the relation should have been made.
    $results = $this->entityMetaRelationStorage->getQuery()->allRevisions()->execute();
    $this->assertCount(1, $results);
    $this->assertEquals(1, $entity_meta_relation->getRevisionId());
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $related_entity_meta = reset($related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta->getRevisionId());
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($related_entity_meta);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    // It's the second node so it has the revision ID of 2.
    $this->assertEquals(2, $related_node->getRevisionId());

    // Update the node alone and check that the relation was updated to point
    // to the new node revision.
    $node->set('title', 'Second node updated');
    $node->setNewRevision(TRUE);
    $node->save();
    $this->nodeStorage->resetCache();
    $node_revision_ids = $this->nodeStorage->getQuery()->condition('nid', 2)->allRevisions()->execute();
    $this->assertCount(2, $node_revision_ids);
    end($node_revision_ids);
    $last_node_revision_id = key($node_revision_ids);
    $this->assertEquals(3, $last_node_revision_id);

    // Only one entity meta relation entity should still exist.
    $this->entityMetaRelationStorage->resetCache();
    $this->assertCount(1, $this->entityMetaRelationStorage->loadMultiple());
    $entity_meta_relation = $this->entityMetaRelationStorage->load(1);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    // Since the second node got updated, the relation got a new revision that
    // points to that new node revision: 3.
    $this->assertEquals(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);
    // Check that we have two entity relation revisions.
    $entity_meta_relation_revision_ids = $this->entityMetaRelationStorage->getQuery()->condition('id', 1)->allRevisions()->execute();
    $this->assertCount(2, $entity_meta_relation_revision_ids);
    // Assert that the first revision still points to the first revision of the
    // second node.
    $entity_meta_relation_revision = $this->entityMetaRelationStorage->loadRevision(1);
    $this->assertEquals(1, $entity_meta_relation_revision->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation_revision->get('emr_node_revision')->target_revision_id);

    // Load the last revision of the second node.
    $node = $this->nodeStorage->loadRevision(3);
    // Check the getRelatedEntities() storage method now that we have a new
    // revision of the node and relation.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $related_entity_meta = reset($related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta->getRevisionId());
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($related_entity_meta);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    // It's the second node so it has the ID of 3 because it's revision got
    // increased.
    $this->assertEquals(3, $related_node->getRevisionId());

    // Update only the entity meta and check that the revision gets updated.
    $entity_meta->set('field_color', 'green');
    $entity_meta->setNewRevision(TRUE);
    $entity_meta->save();
    $this->nodeStorage->resetCache();
    $node_new = $this->nodeStorage->loadRevision($this->nodeStorage->getLatestRevisionId($node->id()));
    // No change in the node itself.
    $this->assertEquals(3, $node_new->getRevisionId());
    $this->entityMetaRelationStorage->resetCache();
    $this->assertCount(1, $this->entityMetaRelationStorage->loadMultiple());
    $entity_meta_relation = $this->entityMetaRelationStorage->load(1);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    // The entity meta revision got increased.
    $this->assertEquals(2, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node_new);
    $this->assertCount(1, $related_entity_meta_entities);
    $related_entity_meta = reset($related_entity_meta_entities);
    $this->assertEquals(2, $related_entity_meta->getRevisionId());

    // Check that we can retrieve the related content entities of a meta entity.
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($entity_meta);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    // We used the latest entity meta to retrieve the related nodes, so the
    // revision ID of the retrieved node should be the latest.
    $this->assertEquals(3, $related_node->getRevisionId());

    // Check that we can retrieve the related content entities of a meta entity
    // also using older revisions.
    $entity_meta = $this->entityMetaStorage->loadRevision(1);
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($entity_meta);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    $this->assertEquals(3, $related_node->getRevisionId());

    // Check that the storage method for finding related meta entities works
    // also with older revisions.
    $node = $this->nodeStorage->loadRevision(2);
    $entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $entity_meta_entities);
    $entity_meta = reset($entity_meta_entities);
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // Check that if we delete a content entity which has meta relations, they
    // get deleted as well.
    $node->delete();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();
    $this->assertEmpty($this->entityMetaStorage->loadMultiple());
    $this->assertEmpty($this->entityMetaRelationStorage->loadMultiple());
  }

  /**
   * Tests that entity meta can be correctly related to content entities (node).
   */
  public function testMultipleEntityMetaRelations() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_multi_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // Asserts that node has no relations.
    $entity_meta_relations = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertEmpty($entity_meta_relations);

    // Create entity meta for bundle "visual".
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta_visual */
    $entity_meta_visual = $this->entityMetaStorage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);
    $entity_meta_visual->save();
    $this->entityMetaStorage->resetCache();
    $entity_meta_visual = $this->entityMetaStorage->loadRevision(1);
    $this->assertEquals('red', $entity_meta_visual->get('field_color')->value);

    // Create entity meta for bundle "audio".
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta_audio */
    $entity_meta_audio = $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);
    $entity_meta_audio->save();
    $this->entityMetaStorage->resetCache();
    $entity_meta_audio = $this->entityMetaStorage->loadRevision(2);
    $this->assertEquals('low', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals('low', $entity_meta_audio->getWrapper()->getVolume());

    // No host entity was specified so there should be no relations between the
    // the node and the entity meta.
    $this->entityMetaRelationStorage->resetCache();
    $this->assertEmpty($this->entityMetaRelationStorage->loadMultiple());

    // Set a host entity onto the entity metas and re-save to make a
    // relationship between them.
    $entity_meta_visual->setHostEntity($node);
    $entity_meta_visual->save();
    $entity_meta_audio->setHostEntity($node);
    $entity_meta_audio->save();

    // No entity meta values have been changed so no new revision should have
    // been created.
    $this->entityMetaStorage->resetCache();
    $results = $this->entityMetaStorage->getQuery()->allRevisions()->execute();
    $this->assertCount(2, $results);

    $entity_meta_visual = $this->entityMetaStorage->load($entity_meta_visual->id());
    $entity_meta_audio = $this->entityMetaStorage->load($entity_meta_audio->id());
    $this->assertEquals(1, $entity_meta_visual->getRevisionId());
    $this->assertEquals(2, $entity_meta_audio->getRevisionId());

    // A relationship between the node and the entity meta should have been
    // created.
    $entity_meta_relations = $this->entityMetaRelationStorage->loadMultiple();
    $this->assertCount(2, $entity_meta_relations);
    // The first relation points to the Visual meta.
    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface $entity_meta_relation */
    $entity_meta_relation = array_shift($entity_meta_relations);
    $this->assertEquals(1, $entity_meta_relation->getRevisionId());
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_revision_id);
    // The second relation points to the Audio meta.
    $entity_meta_relation = array_shift($entity_meta_relations);
    $this->assertEquals(2, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $entity_meta_visual = $this->getEntityMetaList($node)->getEntityMeta('visual');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals(1, $entity_meta_visual->getRevisionId());
    $this->assertEquals(2, $entity_meta_audio->getRevisionId());
    $this->assertEquals($entity_meta_visual->get('field_color')->value, 'red');
    $this->assertEquals($entity_meta_audio->getWrapper()->getVolume(), 'low');

    // Update the node alone and check that the relation was updated to point
    // to the new node revision.
    $node->set('title', 'Second node updated');
    $node->setNewRevision(TRUE);
    $node->save();
    $this->nodeStorage->resetCache();
    // The latest revision of the node is 3.
    $node_new = $this->nodeStorage->loadRevision(3);
    // Two entity meta relation entities should still exist.
    $this->entityMetaRelationStorage->resetCache();
    $this->assertCount(2, $this->entityMetaRelationStorage->loadMultiple());
    $entity_meta_relation = $this->entityMetaRelationStorage->load(1);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    $entity_meta_relation = $this->entityMetaRelationStorage->load(2);
    $this->assertEquals(2, $entity_meta_relation->get('emr_meta_revision')->target_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node_new);
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('visual', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals(2, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());

    // Update only one entity meta and check that the revision gets updated.
    $related_entity_meta_entities['entity_meta:1']->set('field_color', 'green');
    $related_entity_meta_entities['entity_meta:1']->setNewRevision(TRUE);
    $related_entity_meta_entities['entity_meta:1']->setHostEntity($node_new);
    $related_entity_meta_entities['entity_meta:1']->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node_new = $this->nodeStorage->loadRevision(3);
    $this->assertCount(2, $this->entityMetaRelationStorage->loadMultiple());
    $entity_meta_relation = $this->entityMetaRelationStorage->load(1);
    $this->assertEquals(1, $entity_meta_relation->get('emr_meta_revision')->target_id);
    // The new revision of the Visual entity meta: 3.
    $this->assertEquals(3, $entity_meta_relation->get('emr_meta_revision')->target_revision_id);
    $this->assertEquals(2, $entity_meta_relation->get('emr_node_revision')->target_id);
    $this->assertEquals(3, $entity_meta_relation->get('emr_node_revision')->target_revision_id);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node_new);
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals(3, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('visual', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals(2, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('green', $related_entity_meta_entities['entity_meta:1']->get('field_color')->value);
    // Try also with the previous revision of the node.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($this->nodeStorage->loadRevision(2));
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('visual', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals(2, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('red', $related_entity_meta_entities['entity_meta:1']->get('field_color')->value);

    // Check that we can retrieve the related content entities of a meta entity.
    $entity_meta_visual = $this->entityMetaStorage->loadRevision(3);
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($entity_meta_visual);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    // We used the latest entity meta to retrieve the related nodes, so the
    // revision ID of the retrieved node should be the latest.
    $this->assertEquals(3, $related_node->getRevisionId());

    // Use the first revision of the Visual entity meta and load the related
    // nodes. This should also return the latest version of the node.
    $entity_meta = $this->entityMetaStorage->loadRevision(1);
    $related_nodes = $this->entityMetaStorage->getRelatedEntities($entity_meta);
    $this->assertCount(1, $related_nodes);
    $related_node = reset($related_nodes);
    $this->assertEquals(3, $related_node->getRevisionId());

    // Check that if we delete a content entity which has meta relations, they
    // get deleted as well.
    $related_node->delete();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();
    $this->assertEmpty($this->entityMetaStorage->loadMultiple());
    $this->assertEmpty($this->entityMetaRelationStorage->loadMultiple());
  }

  /**
   * Tests that entity metas can be attached to content entities.
   */
  public function testContentEntityAttach() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_multi_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // There are no entity metas yet.
    $this->assertEmpty($this->entityMetaStorage->loadMultiple());

    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $this->assertTrue($entity_meta_speed->isNew());
    $entity_meta_speed->getWrapper()->setGear(1);

    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $entity_meta_audio->getWrapper()->setVolume('low');

    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals(1, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('speed', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals('1', $related_entity_meta_entities['entity_meta:1']->getWrapper()->getGear());
    $this->assertEquals(2, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('low', $related_entity_meta_entities['entity_meta:2']->getWrapper()->getVolume());

    $node = $this->nodeStorage->load(2);
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');

    // Change entity meta values.
    $entity_meta_audio->getWrapper()->setVolume('high');
    $entity_meta_speed->getWrapper()->setGear('2');

    // Attach the changed entity metas by updating the node with a new revision.
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    // The revision got increased.
    $this->assertEquals(3, $node->getRevisionId());
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);
    // Entity meta revision IDs and values have been increased.
    $this->assertEquals(3, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('speed', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals('2', $related_entity_meta_entities['entity_meta:1']->getWrapper()->getGear());
    $this->assertEquals(4, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('high', $related_entity_meta_entities['entity_meta:2']->getWrapper()->getVolume());
    // Check that we can retrieve the same values from the list.
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertEquals('2', $entity_meta_speed->getWrapper()->getGear());
    $this->assertEquals('high', $entity_meta_audio->getWrapper()->getVolume());

    // Load the related entity meta entities of the previous node revision.
    $older_second_node = $this->nodeStorage->loadRevision(2);
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($older_second_node);
    $this->assertCount(2, $related_entity_meta_entities);
    // Entity meta revision IDs and values should be the first ones.
    $this->assertEquals(1, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('speed', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals('1', $related_entity_meta_entities['entity_meta:1']->getWrapper()->getGear());
    $this->assertEquals(2, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('low', $related_entity_meta_entities['entity_meta:2']->getWrapper()->getVolume());
    // Check that we can retrieve the same values from the list.
    $entity_meta_speed = $this->getEntityMetaList($older_second_node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($older_second_node)->getEntityMeta('audio');
    $this->assertEquals('1', $entity_meta_speed->getWrapper()->getGear());
    $this->assertEquals('low', $entity_meta_audio->getWrapper()->getVolume());

    // Update the entity metas without a new node revision.
    $this->assertCount(2, $this->nodeStorage->getQuery()->condition('nid', 2)->allRevisions()->execute());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio->getWrapper()->setVolume('medium');
    $entity_meta_speed->getWrapper()->setGear('3');
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    // Still only two node revisions remain.
    $this->assertCount(2, $this->nodeStorage->getQuery()->condition('nid', 2)->allRevisions()->execute());

    // Assert the correct new entity meta values are loaded. Since we didn't
    // make a new revision of the node, the entity meta values should have
    // not been changed.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertEquals(3, $related_entity_meta_entities['entity_meta:1']->getRevisionId());
    $this->assertEquals('speed', $related_entity_meta_entities['entity_meta:1']->bundle());
    $this->assertEquals('3', $related_entity_meta_entities['entity_meta:1']->getWrapper()->getGear());
    $this->assertEquals(4, $related_entity_meta_entities['entity_meta:2']->getRevisionId());
    $this->assertEquals('audio', $related_entity_meta_entities['entity_meta:2']->bundle());
    $this->assertEquals('medium', $related_entity_meta_entities['entity_meta:2']->getWrapper()->getVolume());
    // Check that we can retrieve the same values from the list.
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertEquals('3', $entity_meta_speed->getWrapper()->getGear());
    $this->assertEquals('medium', $entity_meta_audio->getWrapper()->getVolume());
  }

  /**
   * Tests that entity metas can be detached from content entities.
   */
  public function testContentEntityDetach() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_multi_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // Create a speed meta.
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_speed->getWrapper()->setGear(1);

    // Create an audio meta.
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $entity_meta_audio->getWrapper()->setVolume('low');

    // Attach them to the node.
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    // Update the meta values.
    $entity_meta_audio->getWrapper()->setVolume('high');
    $entity_meta_speed->getWrapper()->setGear('2');

    // Attach the metas again but this time by increasing the node revision.
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    // Update the meta values and attach again without node revision update.
    $entity_meta_audio->getWrapper()->setVolume('medium');
    $entity_meta_speed->getWrapper()->setGear('3');
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $node->save();

    // Detach audio entity meta without making a new content revision. This
    // means that relation revisions will need to be deleted so we should
    // establish a baseline of the revisions for the relation with the ID 2.
    $relation_revisions = $this->entityMetaRelationStorage->getQuery()->condition('id', 2)->allRevisions()->execute();
    $this->assertCount(3, $relation_revisions);
    $this->assertEquals([2, 4, 6], array_keys($relation_revisions));
    $revision = $this->entityMetaRelationStorage->loadRevision(6);
    $this->assertTrue($revision->isDefaultRevision());
    // There are 4 entity meta revisions, two for each meta.
    $this->assertCount(4, $this->entityMetaStorage->getQuery()->allRevisions()->execute());

    $this->getEntityMetaList($node)->detach($entity_meta_audio);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    // No new node revision was made.
    $this->assertCount(2, $this->nodeStorage->getQuery()->condition('nid', 2)->allRevisions()->execute());
    // Since there were two relation revisions pointing to this Node revision
    // (due to 2 different meta revisions), both of these should be deleted.
    $relation_revisions = $this->entityMetaRelationStorage->getQuery()->condition('id', 2)->allRevisions()->execute();
    $this->assertCount(1, $relation_revisions);
    $this->assertEquals([2], array_keys($relation_revisions));
    $revision = $this->entityMetaRelationStorage->loadRevision(2);
    $this->assertTrue($revision->isDefaultRevision());
    // One of the entity meta revisions became orphaned so it should have been
    // deleted.
    $this->assertCount(3, $this->entityMetaStorage->getQuery()->allRevisions()->execute());

    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    // Only the speed entity meta should remain attached though on the latest
    // node revision.
    $this->assertCount(1, $related_entity_meta_entities);
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');

    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals('3', $entity_meta_speed->getWrapper()->getGear());
    // Since the audio meta was detached, it should be returned as a new entity.
    $this->assertTrue($entity_meta_audio->isNew());

    // We detached the audio meta from the latest revision of the node, but
    // we should still see it attached on the first.
    $old_node_revision = $this->nodeStorage->loadRevision(2);
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($old_node_revision);
    $this->assertEquals(['entity_meta:1', 'entity_meta:2'], array_keys($related_entity_meta_entities));
    $entity_meta_audio = $this->getEntityMetaList($old_node_revision)->getEntityMeta('audio');
    $this->assertEquals(2, $entity_meta_audio->id());

    // Detach also from this node revision without making a new revision.
    $this->getEntityMetaList($old_node_revision)->detach($entity_meta_audio);
    $old_node_revision->save();

    // It was the last relation revision so it should have been deleted.
    $this->assertNull($this->entityMetaRelationStorage->load(2));

    // Since no relations point to audio meta anymore, it should also have been
    // deleted.
    $this->entityMetaStorage->resetCache();
    $this->assertNull($this->entityMetaStorage->load(2));
    // And only two entity meta revisions left.
    $this->assertCount(2, $this->entityMetaStorage->getQuery()->allRevisions()->execute());

    $node = $this->nodeStorage->load(2);

    // Detach the speed entity entity meta from the last node revision as well.
    $this->getEntityMetaList($node)->detach($entity_meta_speed);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(0, $related_entity_meta_entities);
    $this->assertNull($entity_meta_speed->id());
    $this->assertNull($entity_meta_audio->id());

    // Since we detached the speed meta from the last revision of the node,
    // the only speed meta revision left should be the one attached to the
    // older revision of the node.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($old_node_revision);
    $this->assertEquals(['entity_meta:1'], array_keys($related_entity_meta_entities));
    $entity_meta_speed = $this->getEntityMetaList($old_node_revision)->getEntityMeta('speed');
    $this->assertEquals(1, $entity_meta_speed->id());
    // Additionally, there should be no orphan revisions of the speed meta.
    $revision_ids = $this->entityMetaStorage->getQuery()->allRevisions()->execute();
    $this->assertEquals([1], array_keys($revision_ids));

    // Reattach the two metas (one already existing on the first revision and
    // a new one) to the latest node revision and increase the revision.
    $entity_meta_audio->getWrapper()->setVolume('low');
    $entity_meta_speed->getWrapper()->setGear('3');

    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->attach($entity_meta_speed);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    // Detach the audio meta from the latest node revision but this time by
    // making a new node revision in the process to test that the revision
    // creation gets skipped.
    $this->assertCount(4, $this->entityMetaRelationStorage->getQuery()->allRevisions()->execute());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->detach($entity_meta_audio);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $this->assertEquals(5, $node->getRevisionId());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals('3', $entity_meta_speed->getWrapper()->getGear());
    $this->assertTrue($entity_meta_audio->isNew());

    // Detach second entity meta.
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->detach($entity_meta_speed);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $this->assertEquals(6, $node->getRevisionId());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(0, $related_entity_meta_entities);
    $this->assertTrue($entity_meta_speed->isNew());
    $this->assertTrue($entity_meta_audio->isNew());

    // The previous node revisions should still have the same related metas.
    $node = $this->nodeStorage->loadRevision(5);
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals('3', $entity_meta_speed->getWrapper()->getGear());
    $this->assertTrue($entity_meta_audio->isNew());
  }

  /**
   * Test setting entity metas using the field API setter.
   */
  public function testContentEntitySet() {
    /** @var \Drupal\node\NodeInterface $first_node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_multi_example_ct',
      'title' => 'Node test',
    ]);
    $entity_meta_speed = $this->entityMetaStorage->create([
      'bundle' => 'speed',
      'field_gear' => '3',
    ]);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta_audio = $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);

    $node->set('emr_entity_metas', [$entity_meta_speed, $entity_meta_audio]);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $this->assertEquals(2, $node->getRevisionId());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);
    $this->assertEquals($entity_meta_speed->getWrapper()->getGear(), '3');
    $this->assertEquals($entity_meta_audio->getWrapper()->getVolume(), 'low');

    // Change entity metas through the entity.
    $entity_meta_audio->getWrapper()->setVolume('high');
    $entity_meta_speed->getWrapper()->setGear('1');

    $node->set('emr_entity_metas', $entity_meta_speed);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $this->assertEquals(2, $node->getRevisionId());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals($entity_meta_speed->getWrapper()->getGear(), '1');
    $this->assertNull($entity_meta_audio->id());

    // Change entity metas directly.
    $entity_meta_speed->getWrapper()->setGear('3');
    $entity_meta_speed->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $this->entityMetaRelationStorage->resetCache();

    $node = $this->nodeStorage->load(2);
    $this->assertEquals(2, $node->getRevisionId());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_entity_meta_entities);
    $this->assertEquals($entity_meta_speed->getWrapper()->getGear(), '3');
    $this->assertTrue($entity_meta_audio->isNew());
  }

  /**
   * Tests the entity meta default marking without a host.
   */
  public function testEntityMetaDefaultRevisionsNoHost(): void {
    // Create two entity meta entities to test with.
    $this->entityMetaStorage->create([
      'bundle' => 'speed',
      'field_gear' => '3',
    ])->save();
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ])->save();

    // Assert regular loading and values.
    $this->entityMetaStorage->resetCache();
    $entity_meta_audio = $this->entityMetaStorage->load(2);
    $this->assertEquals(2, $entity_meta_audio->getRevisionId());
    $this->assertEquals('low', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Assert loading by properties.
    $this->entityMetaStorage->resetCache();
    $entity_metas = $this->entityMetaStorage->loadByProperties(['field_volume' => 'low']);
    $this->assertCount(1, $entity_metas);
    $entity_meta_audio = reset($entity_metas);
    $this->assertEquals(2, $entity_meta_audio->getRevisionId());
    $this->assertEquals('low', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Assert querying by value.
    $ids = $this->entityMetaStorage->getQuery()->condition('field_volume', 'low')->execute();
    $this->assertEquals([2 => 2], $ids);

    // Update the audio meta and make a new default revision.
    $this->entityMetaStorage->resetCache();
    $entity_meta_audio->set('field_volume', 'medium');
    $entity_meta_audio->setNewRevision(TRUE);
    $entity_meta_audio->save();
    $this->assertCount(2, $this->entityMetaStorage->loadMultipleRevisions($this->entityMetaStorage->revisionIds($entity_meta_audio)));

    // Assert regular loading and values.
    $entity_meta_audio = $this->entityMetaStorage->load(2);
    $this->assertEquals(3, $entity_meta_audio->getRevisionId());
    $this->assertEquals('medium', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);
    $first_revision = $this->entityMetaStorage->loadRevision(2);
    $this->assertEquals('low', $first_revision->get('field_volume')->value);
    $this->assertEquals(0, $first_revision->get('emr_default_revision')->value);

    // Assert loading by properties.
    $this->entityMetaStorage->resetCache();
    $entity_metas = $this->entityMetaStorage->loadByProperties(['field_volume' => 'low']);
    $this->assertCount(0, $entity_metas);
    $entity_metas = $this->entityMetaStorage->loadByProperties(['field_volume' => 'medium']);
    $this->assertCount(1, $entity_metas);
    $entity_meta_audio = reset($entity_metas);
    $this->assertEquals(3, $entity_meta_audio->getRevisionId());
    $this->assertEquals('medium', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Assert querying by value.
    $ids = $this->entityMetaStorage->getQuery()->condition('field_volume', 'medium')->execute();
    $this->assertEquals([3 => 2], $ids);

    // Change the default revision of the audio meta.
    $first_revision = $this->entityMetaStorage->loadRevision(2);
    $first_revision->set('emr_default_revision', TRUE);
    $first_revision->_original = $first_revision;
    $first_revision->setNewRevision(FALSE);
    $first_revision->save();
    // No new revision should be made.
    $this->assertCount(2, $this->entityMetaStorage->loadMultipleRevisions($this->entityMetaStorage->revisionIds($entity_meta_audio)));
    $this->entityMetaStorage->resetCache();
    $first_revision = $this->entityMetaStorage->loadRevision(2);
    $this->assertEquals(1, $first_revision->get('emr_default_revision')->value);
    $second_revision = $this->entityMetaStorage->loadRevision(3);
    // The previously default revision should no longer be default.
    $this->assertEquals(0, $second_revision->get('emr_default_revision')->value);

    // Assert querying by value.
    $ids = $this->entityMetaStorage->getQuery()->condition('field_volume', 'medium')->execute();
    $this->assertEmpty($ids);

    $ids = $this->entityMetaStorage->getQuery()->condition('field_volume', 'low')->execute();
    $this->assertEquals([2 => 2], $ids);
  }

  /**
   * Tests the entity meta default marking with a host.
   */
  public function testEntityMetaDefaultRevisionsWithHost(): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());
    $this->assertTrue(2, $node->isDefaultRevision());

    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertTrue($entity_meta_audio->isNew());
    $entity_meta_audio->getWrapper()->setVolume('low');

    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $entity_meta_audio = $this->entityMetaStorage->load(1);
    $this->assertEquals(1, $entity_meta_audio->getRevisionId());
    $this->assertEquals('low', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Update the meta with a new node revision.
    $entity_meta_audio->getWrapper()->setVolume('medium');
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    $this->assertEquals(3, $node->getRevisionId());
    $this->assertTrue($node->isDefaultRevision());
    $entity_meta_audio = $this->entityMetaStorage->load(1);
    $this->assertEquals(2, $entity_meta_audio->getRevisionId());
    $this->assertEquals('medium', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    $entity_meta_audio_revision = $this->entityMetaStorage->loadRevision(1);
    $this->assertEquals('low', $entity_meta_audio_revision->get('field_volume')->value);
    $this->assertEquals(0, $entity_meta_audio_revision->get('emr_default_revision')->value);

    // Detach the meta by making a new revision of the node.
    $node->setNewRevision(TRUE);
    $this->getEntityMetaList($node)->detach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    $this->assertEquals(4, $node->getRevisionId());
    $this->assertTrue($node->isDefaultRevision());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertTrue($entity_meta_audio->isNew());
    // Since the default revision of the node no longer is linked to the meta,
    // there is no more default meta revision so no metas should be found.
    $this->assertNull($this->entityMetaStorage->load(1));
    $this->assertEmpty($this->entityMetaStorage->getQuery()->execute());
    $this->assertCount(2, $this->entityMetaStorage->getQuery()->allRevisions()->execute());

    // Attach back the audio meta.
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertTrue($entity_meta_audio->isNew());
    $entity_meta_audio->getWrapper()->setVolume('high');
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->setNewRevision(TRUE);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    $this->assertEquals(5, $node->getRevisionId());
    $this->assertTrue($node->isDefaultRevision());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertEquals(3, $entity_meta_audio->getRevisionId());

    // A new meta entity was created after detaching.
    $this->assertEquals(2, $entity_meta_audio->id());
    $this->assertEquals('high', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Update the meta values again.
    $entity_meta_audio->getWrapper()->setVolume('low');
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->setNewRevision(TRUE);
    $node->save();
    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    $this->assertEquals(6, $node->getRevisionId());
    $this->assertTrue($node->isDefaultRevision());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertEquals(4, $entity_meta_audio->getRevisionId());
    $this->assertEquals(2, $entity_meta_audio->id());
    $this->assertEquals('low', $entity_meta_audio->get('field_volume')->value);
    $this->assertEquals(1, $entity_meta_audio->get('emr_default_revision')->value);

    // Check that the previous revision is not default anymore.
    $previous_meta_revision = $this->entityMetaStorage->loadRevision(3);
    $this->assertEquals('high', $previous_meta_revision->get('field_volume')->value);
    $this->assertEquals(0, $previous_meta_revision->get('emr_default_revision')->value);

    // Detach the meta without making a new node revision.
    $this->getEntityMetaList($node)->detach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $this->entityMetaStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    // No new node revision.
    $this->assertEquals(6, $node->getRevisionId());
    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertTrue($entity_meta_audio->isNew());

    // The last meta revision was deleted because the relation to it was deleted
    // so it became orphan.
    $this->assertCount(1, $this->entityMetaStorage->getQuery()->condition('id', 2)->allRevisions()->execute());
    // No default revisions are left on the meta.
    $this->assertEmpty($this->entityMetaStorage->load(2));
    // The remaining revision is not default.
    $this->assertEquals(0, $this->entityMetaStorage->loadRevision(3)->get('emr_default_revision')->value);
  }

  /**
   * Helper method to retrieve the entity meta list field value from a Node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\emr\Field\EntityMetaItemListInterface
   *   The computed list.
   */
  protected function getEntityMetaList(NodeInterface $node): EntityMetaItemListInterface {
    return $node->get('emr_entity_metas');
  }

}
