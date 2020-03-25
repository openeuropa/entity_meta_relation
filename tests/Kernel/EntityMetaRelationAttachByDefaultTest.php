<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that default entity metas are attached by default.
 */
class EntityMetaRelationAttachByDefaultTest extends KernelTestBase {

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
    $emr_installer->installEntityMetaTypeOnContentEntityType('force', 'node');

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

    // Asserts that node has relations.
    $entity_meta_relations = $this->entityMetaStorage->getRelatedEntities($first_node);
    $this->assertNotEmpty($entity_meta_relations);
  }

  /**
   * Tests creating entity meta with default values.
   */
  public function testCreateEntityMetaWithDefaultValues() {
    // Manually create an entity meta for bundle "force".
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'force',
    ]);
    $entity_meta->save();

    // Even if this entity meta has no relations, the first node created
    // relations.
    $this->assertNotEmpty($this->entityMetaRelationStorage->loadMultiple());

    // Asserts that force was correctly saved.
    $this->assertEquals('weak', $entity_meta->getWrapper()->getGravity());
    $this->assertEquals(2, $entity_meta->getRevisionId());
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

    // Asserts that node has relations.
    $entity_meta_relations = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertNotEmpty($entity_meta_relations);

    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    // Second entity meta created.
    $force_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('force');
    $this->assertEquals(2, $force_entity_meta->getRevisionId());

    // Check the force.
    $gravity = $force_entity_meta->getWrapper()->getGravity();
    $this->assertEquals('weak', $gravity);

    $visual_entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);

    $audio_entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);

    $node->get('emr_entity_metas')->attach($visual_entity_meta);
    $node->get('emr_entity_metas')->attach($audio_entity_meta);
    $node->save();

    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    $force_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('force');
    $this->assertEquals(2, $force_entity_meta->getRevisionId());
    $this->assertEquals('weak', $force_entity_meta->getWrapper()->getGravity());

    $visual_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $this->assertEquals(3, $visual_entity_meta->getRevisionId());
    $this->assertEquals('red', $visual_entity_meta->get('field_color')->value);

    $audio_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $this->assertEquals(4, $audio_entity_meta->getRevisionId());
    $this->assertEquals('low', $audio_entity_meta->getWrapper()->getVolume());

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(3, $related_entity_meta_entities);

    // Do the same but with set.
    $speed_entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'speed',
      'field_gear' => '1',
    ]);

    $node->set('emr_entity_metas', [$speed_entity_meta]);
    $node->setNewRevision(TRUE);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);

    // Values are ok.
    $force_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('force');
    $this->assertEquals(2, $force_entity_meta->getRevisionId());
    $this->assertEquals('weak', $force_entity_meta->getWrapper()->getGravity());

    $visual_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $this->assertEquals(5, $visual_entity_meta->getRevisionId());
    $this->assertEquals('1', $visual_entity_meta->getWrapper()->getGear());

    $force_entity_meta->getWrapper()->setGravity('powerful');
    $node->get('emr_entity_metas')->attach($force_entity_meta);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);

    // Values are ok.
    $force_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('force');
    $this->assertEquals(2, $force_entity_meta->getRevisionId());
    $this->assertEquals('powerful', $force_entity_meta->getWrapper()->getGravity());

    $visual_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $this->assertEquals(5, $visual_entity_meta->getRevisionId());
    $this->assertEquals('1', $visual_entity_meta->getWrapper()->getGear());

    // Try to detach.
    $node->get('emr_entity_metas')->detach($force_entity_meta);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    // Check that the storage method for finding related meta entities works.
    $related_entity_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_entity_meta_entities);

    // Values are ok.
    $force_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('force');
    $this->assertEquals(2, $force_entity_meta->getRevisionId());
    $this->assertEquals('powerful', $force_entity_meta->getWrapper()->getGravity());

    $visual_entity_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $this->assertEquals(5, $visual_entity_meta->getRevisionId());
    $this->assertEquals('1', $visual_entity_meta->getWrapper()->getGear());

  }

}
