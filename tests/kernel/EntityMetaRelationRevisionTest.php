<?php

namespace Drupal\Tests\emr\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the entity meta/entity meta relation at revision level.
 */
class EntityMetaRelationRevisionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_meta_example',
    'entity_meta_audio',
    'entity_meta_visual',
    'entity_meta_speed',
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
    $this->installSchema('node', 'node_access', 'emr_node');
    $this->installConfig(
      ['emr', 'emr_node', 'entity_meta_example',
        'entity_meta_audio', 'entity_meta_visual', 'entity_meta_speed',
      ]);

    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $this->entity_meta_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_meta_storage */
    $this->entity_meta_relation_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $this->node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->createNodeWithFourRevisions();
  }

  /**
   * Tests that the revisions were created correctly.
   */
  public function testCreatedRevisions() {
    // First revision.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->node_storage->loadRevision(1);
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Visual was properly saved.
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertFalse($visual_meta->get('status')->value);
    // Audio was properly saved.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
    $this->assertFalse($audio_meta->get('status')->value);
    // Speed was properly saved.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '1');
    $this->assertFalse($speed_meta->get('status')->value);

    // Second revision.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->node_storage->loadRevision(2);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Visual was properly saved.
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio was properly saved.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed was properly saved.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
    $this->assertTrue($speed_meta->get('status')->value);

    // Third revision.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->node_storage->loadRevision(3);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Visual was properly saved.
    $this->assertEquals($visual_meta->get('field_color')->value, 'green');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio was properly saved.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed was properly saved.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
    $this->assertTrue($speed_meta->get('status')->value);

    // Fourth revision.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->node_storage->loadRevision(4);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Visual was properly saved.
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio was properly saved.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed was properly saved.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
    $this->assertTrue($speed_meta->get('status')->value);

    // Assert number of revisions and entity metas and relations.
    $revisions_ids = $this->node_storage->revisionIds($node);
    $this->assertCount(4, $revisions_ids);
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(4, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());
  }

  /**
   * Test content entitity and content entity revisions delete.
   */
  public function testContentEntityRevisionsDelete() {
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(4, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(4, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(3);
    $this->assertCount(4, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));

    // Visual.
    $entity_meta = $this->entity_meta_storage->load(1);
    $this->assertCount(4, $this->entity_meta_storage->revisionIds($entity_meta));
    // Audio.
    $entity_meta = $this->entity_meta_storage->load(2);
    $this->assertCount(2, $this->entity_meta_storage->revisionIds($entity_meta));
    // Speed.
    $entity_meta = $this->entity_meta_storage->load(3);
    $this->assertCount(3, $this->entity_meta_storage->revisionIds($entity_meta));

    // Delete second relation.
    $this->node_storage->deleteRevision(2);

    // We keep having same number of relations but less revisions.
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(3);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));

    // Visual.
    $entity_meta = $this->entity_meta_storage->load(1);
    $this->assertCount(3, $this->entity_meta_storage->revisionIds($entity_meta));
    // Audio.
    $entity_meta = $this->entity_meta_storage->load(2);
    $this->assertCount(2, $this->entity_meta_storage->revisionIds($entity_meta));
    // Speed.
    $entity_meta = $this->entity_meta_storage->load(3);
    $this->assertCount(2, $this->entity_meta_storage->revisionIds($entity_meta));

    $node_first_revision = $this->node_storage->loadRevision(1);
    $audio_meta = $node_first_revision->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node_first_revision->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node_first_revision->get('emr_entity_metas')->getEntityMeta('speed');

    // Color is still valid.
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertFalse($visual_meta->get('status')->value);
    // Audio is still valid.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
    $this->assertFalse($audio_meta->get('status')->value);
    // Speed is still valid.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '1');
    $this->assertFalse($speed_meta->get('status')->value);

    // Delete first revision.
    $this->node_storage->deleteRevision(1);
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(2, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(2, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(3);
    $this->assertCount(2, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));

    // Visual.
    $entity_meta = $this->entity_meta_storage->load(1);
    $this->assertCount(2, $this->entity_meta_storage->revisionIds($entity_meta));
    // Audio.
    $entity_meta = $this->entity_meta_storage->load(2);
    $this->assertCount(1, $this->entity_meta_storage->revisionIds($entity_meta));
    // Speed.
    $entity_meta = $this->entity_meta_storage->load(3);
    $this->assertCount(1, $this->entity_meta_storage->revisionIds($entity_meta));

    $node_final = $this->node_storage->load(1);
    $audio_meta = $node_final->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node_final->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node_final->get('emr_entity_metas')->getEntityMeta('speed');

    // Visual keeps being correct.
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio keeps being correct.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed keeps being correct.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
    $this->assertTrue($speed_meta->get('status')->value);

    // Change again and confirm it is working.
    $visual_meta->set('field_color', 'blue');
    $speed_meta->getWrapper()->setGear('2');
    $node_final->setNewRevision(TRUE);
    $node_final->get('emr_entity_metas')->attach($visual_meta);
    $node_final->get('emr_entity_metas')->attach($speed_meta);
    $node_final->save();

    $node_final = $this->node_storage->load(1);
    $audio_meta = $node_final->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node_final->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node_final->get('emr_entity_metas')->getEntityMeta('speed');

    // Color is still valid.
    $this->assertEquals($visual_meta->get('field_color')->value, 'blue');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio is still valid.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed is still valid.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
    $this->assertTrue($speed_meta->get('status')->value);

    // Visual.
    $entity_meta = $this->entity_meta_storage->load(1);
    $this->assertCount(3, $this->entity_meta_storage->revisionIds($entity_meta));
    // Audio.
    $entity_meta = $this->entity_meta_storage->load(2);
    $this->assertCount(1, $this->entity_meta_storage->revisionIds($entity_meta));
    // Speed.
    $entity_meta = $this->entity_meta_storage->load(3);
    $this->assertCount(2, $this->entity_meta_storage->revisionIds($entity_meta));

    // Counts are still valid.
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    $entity_meta_relation = $this->entity_meta_relation_storage->load(3);
    $this->assertCount(3, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
  }

  /**
   * Test content entity revisions revert.
   */
  public function testContentEntityRevisionsRevert() {
    $node = $this->node_storage->load(1);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertTrue($visual_meta->get('status')->value);
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
    $this->assertTrue($speed_meta->get('status')->value);

    // Revert first revision.
    /** @var \Drupal\node\NodeInterface $node_first_revision */
    $node_first_revision = $this->node_storage->loadRevision(1);
    $node_first_revision->setNewRevision(TRUE);
    $node_first_revision->isDefaultRevision(TRUE);
    $node_first_revision->save();
    $node = $this->node_storage->load(1);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $this->assertEquals($visual_meta->get('field_color')->value, 'red');
    $this->assertFalse($visual_meta->get('status')->value);
    $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
    $this->assertFalse($audio_meta->get('status')->value);
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '1');
    $this->assertFalse($speed_meta->get('status')->value);

    // Revisions and relation counts are correct.
    $revisions_ids = $this->node_storage->revisionIds($node);
    $this->assertCount(5, $revisions_ids);
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());

    // Visual.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(5, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    // Audio.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(5, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    // Speed.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(5, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));

    $node = $this->node_storage->load(1);
    // Change again and confirm it is working.
    $visual_meta->set('field_color', 'blue');
    $speed_meta->getWrapper()->setGear('2');
    $node->get('emr_entity_metas')->attach($visual_meta);
    $node->get('emr_entity_metas')->attach($speed_meta);
    $node->setNewRevision(TRUE);
    $node->save();

    $node_final = $this->node_storage->load(1);
    $audio_meta = $node_final->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node_final->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node_final->get('emr_entity_metas')->getEntityMeta('speed');

    // Color is still valid.
    $this->assertEquals($visual_meta->get('field_color')->value, 'blue');
    $this->assertFalse($visual_meta->get('status')->value);
    // Audio is still valid.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
    $this->assertFalse($audio_meta->get('status')->value);
    // Speed is still valid.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
    $this->assertFalse($speed_meta->get('status')->value);

    // Revisions and relation counts are correct.
    $revisions_ids = $this->node_storage->revisionIds($node);
    $this->assertCount(6, $revisions_ids);
    $this->assertCount(3, $this->entity_meta_relation_storage->loadMultiple());
    $this->assertCount(3, $this->entity_meta_storage->loadMultiple());

    // Visual.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(1);
    $this->assertCount(6, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    // Audio.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(6, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));
    // Speed.
    $entity_meta_relation = $this->entity_meta_relation_storage->load(2);
    $this->assertCount(6, $this->entity_meta_relation_storage->revisionIds($entity_meta_relation));

  }

  /**
   * Create a node with changes done through three revisions.
   */
  protected function createNodeWithFourRevisions() {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_meta_storage */
    $entity_meta_relation_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create([
      'type' => 'entity_meta_multi_example',
      'title' => 'Node test',
    ]);
    $node->setPublished(FALSE);
    $entity_metas = [];
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_metas[0] = $entity_meta_storage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_metas[1] = $entity_meta_storage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    $entity_metas[2] = $entity_meta_storage->create([
      'bundle' => 'speed',
      'field_gear' => '1',
    ]);
    $node->set('emr_entity_metas', $entity_metas);
    $node->save();

    // Second revision - Change node status, audio and volume.
    $node->setNewRevision(TRUE);
    $node->setPublished(TRUE);
    $node->isDefaultRevision(TRUE);

    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $audio_meta->getWrapper()->setVolume('medium');
    $speed_meta->getWrapper()->setGear('2');
    $node->get('emr_entity_metas')->attach($audio_meta);
    $node->get('emr_entity_metas')->attach($speed_meta);
    $node->save();

    // Third revision - Change visual and speed.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load(1);
    $entity_meta_visual = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $entity_meta_speed = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $entity_meta_visual->set('field_color', 'green');
    $entity_meta_speed->getWrapper()->setGear('3');
    $node->get('emr_entity_metas')->attach($entity_meta_visual);
    $node->get('emr_entity_metas')->attach($entity_meta_speed);
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();

    // Fourth revision - Change visual.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load(1);
    $entity_meta_visual = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $entity_meta_visual->set('field_color', 'red');
    $node->get('emr_entity_metas')->attach($entity_meta_visual);
    $node->setNewRevision(TRUE);
    $node->isDefaultRevision(TRUE);
    $node->save();
  }

}
