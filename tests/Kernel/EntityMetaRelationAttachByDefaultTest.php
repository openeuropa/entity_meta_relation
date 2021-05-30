<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\emr\Field\EntityMetaItemListInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;

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
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_meta');
    $this->installEntitySchema('entity_meta_relation');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('emr', ['entity_meta_default_revision']);
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

    // We create a first node, we don't use for anything to ensure we don't
    // have coincidental matching IDs between nodes and entity metas.
    /** @var \Drupal\node\NodeInterface $first_node */
    $first_node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'First node',
    ]);
    $first_node->save();
    $this->assertEquals(1, $first_node->getRevisionId());

    // The Force entity meta sets default values, the others do not.
    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($first_node);
    $this->assertCount(1, $related_meta_entities);
    $entity_meta = reset($related_meta_entities);
    $this->assertEquals('force', $entity_meta->bundle());
    $this->assertEquals('weak', $entity_meta->getWrapper()->getGravity());
  }

  /**
   * Tests entity meta factory with default values.
   *
   * Tests that creating an EntityMeta entity using the storage adds default
   * values when needed.
   */
  public function testCreateEntityMetaWithDefaultValues() {
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'force',
    ]);
    $entity_meta->save();

    // Assert that force EntityMeta has default values.
    $this->assertEquals('weak', $entity_meta->getWrapper()->getGravity());

    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_meta = $this->entityMetaStorage->create([
      'bundle' => 'audio',
    ]);
    $entity_meta->save();
    // Assert that audio EntityMeta has NO default values.
    $this->assertNull($entity_meta->getWrapper()->getVolume());
  }

  /**
   * Tests that entity metas can be created with default values.
   */
  public function testApiWithDefaultValues() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'Second node',
    ]);
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // Also the second node has one default meta.
    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(1, $related_meta_entities);
    $entity_meta_force = reset($related_meta_entities);
    $this->assertEquals(2, $entity_meta_force->getRevisionId());
    $this->assertEquals('force', $entity_meta_force->bundle());
    $this->assertEquals('weak', $entity_meta_force->getWrapper()->getGravity());
    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $this->assertEquals(2, $entity_meta_force->getRevisionId());

    $entity_meta_visual = $this->entityMetaStorage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);

    $entity_meta_audio = $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);

    $this->getEntityMetaList($node)->attach($entity_meta_visual);
    $this->getEntityMetaList($node)->attach($entity_meta_audio);
    $node->save();

    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $this->assertEquals(2, $entity_meta_force->getRevisionId());
    $this->assertEquals('weak', $entity_meta_force->getWrapper()->getGravity());

    $entity_meta_visual = $this->getEntityMetaList($node)->getEntityMeta('visual');
    $this->assertEquals(3, $entity_meta_visual->getRevisionId());
    $this->assertEquals('red', $entity_meta_visual->get('field_color')->value);

    $entity_meta_audio = $this->getEntityMetaList($node)->getEntityMeta('audio');
    $this->assertEquals(4, $entity_meta_audio->getRevisionId());
    $this->assertEquals('low', $entity_meta_audio->getWrapper()->getVolume());

    // Check that the storage method for finding related meta entities works.
    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(3, $related_meta_entities);

    // Override the list of metas with a new set of only one meta.
    $entity_meta_speed = $this->entityMetaStorage->create([
      'bundle' => 'speed',
      'field_gear' => '1',
    ]);

    $node->set('emr_entity_metas', [$entity_meta_speed]);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);

    // The default force EntityMeta should be gone.
    $this->assertCount(1, $related_meta_entities);
    $entity_meta_speed = reset($related_meta_entities);
    $this->assertEquals(5, $entity_meta_speed->getRevisionId());
    $this->assertEquals('speed', $entity_meta_speed->bundle());
    $this->assertEquals('1', $entity_meta_speed->getWrapper()->getGear());
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $this->assertEquals(5, $entity_meta_speed->getRevisionId());

    // Attach back the Force EntityMeta with new values.
    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $entity_meta_force->getWrapper()->setGravity('powerful');
    $this->assertTrue((boolean) $entity_meta_force->isNew());
    $this->getEntityMetaList($node)->attach($entity_meta_force);
    $node->save();

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);

    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertCount(2, $related_meta_entities);
    $entity_meta_speed = $this->getEntityMetaList($node)->getEntityMeta('speed');
    $this->assertEquals('1', $entity_meta_speed->getWrapper()->getGear());
    $this->assertEquals(5, $entity_meta_speed->getRevisionId());
    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $this->assertEquals(6, $entity_meta_force->getRevisionId());
    $this->assertEquals('powerful', $entity_meta_force->getWrapper()->getGravity());
  }

  /**
   * Tests that the host entity can skip the presetting of defaults in the meta.
   */
  public function testApiWithSkippingDefaultValues() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_example_ct',
      'title' => 'Second node',
    ]);
    $node->entity_meta_no_default = TRUE;
    $node->save();
    $this->assertEquals(2, $node->getRevisionId());

    // Since we marked the host entity not to have any defaults, we should not
    // have any entity metas for this node, as it would be expected.
    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertEmpty($related_meta_entities);
    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $this->assertTrue((boolean) $entity_meta_force->isNew());

    $this->entityMetaStorage->resetCache();
    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(2);
    $node->save();

    $related_meta_entities = $this->entityMetaStorage->getRelatedEntities($node);
    $this->assertEmpty($related_meta_entities);
    $entity_meta_force = $this->getEntityMetaList($node)->getEntityMeta('force');
    $this->assertTrue((boolean) $entity_meta_force->isNew());
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
