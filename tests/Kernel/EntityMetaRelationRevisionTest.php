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
        'entity_meta_audio', 'entity_meta_visual', 'entity_meta_speed',
      ]);

    $this->entityMetaStorage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    $this->entityMetaRelationStorage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->createNodeWithFourRevisions();
  }

  /**
   * Test content entity revision delete.
   */
  public function testContentEntityRevisionsDelete() {
    // We start by deleting the second node revision.
    $this->nodeStorage->deleteRevision(2);
    $this->assertCount(3, $this->nodeStorage->revisionIds($this->nodeStorage->load(1)));

    // We keep having the same number of relations and metas, but one less
    // revision.
    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));
    $this->assertCount(3, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    $this->assertCount(3, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(3, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));

    $this->assertEntityMetaRevisionValues(1);
    $this->assertEntityMetaRevisionValues(3);
    $this->assertEntityMetaRevisionValues(4);

    // Delete first revision.
    $this->nodeStorage->deleteRevision(1);
    // We keep having the same number of relations and metas, but again one less
    // revision.
    $this->assertCount(2, $this->nodeStorage->revisionIds($this->nodeStorage->load(1)));
    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());
    $this->assertCount(2, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(2, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(2, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));
    $this->assertCount(2, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    $this->assertCount(2, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(2, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));

    $this->assertEntityMetaRevisionValues(3);
    $this->assertEntityMetaRevisionValues(4);

    $node = $this->nodeStorage->load(1);
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Change again meta values and confirm it is working.
    $visual_meta->set('field_color', 'blue');
    $speed_meta->getWrapper()->setGear('2');
    $node->setNewRevision(TRUE);
    $node->get('emr_entity_metas')->attach($visual_meta);
    $node->get('emr_entity_metas')->attach($speed_meta);
    $node->save();

    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->load(1);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    // Color has changed.
    $this->assertEquals($visual_meta->get('field_color')->value, 'blue');
    $this->assertTrue($visual_meta->get('status')->value);
    // Audio is still as it was.
    $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
    $this->assertTrue($audio_meta->get('status')->value);
    // Speed has changed as well.
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
    $this->assertTrue($speed_meta->get('status')->value);

    // One extra node revision exists now, so one extra relation and meta
    // revision should exist.
    $this->assertCount(3, $this->nodeStorage->revisionIds($node));
    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(3, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));
    $this->assertCount(3, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    // The audio meta was not changed so there is no increase in revisions.
    $this->assertCount(2, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(3, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));
  }

  /**
   * Test content entity revision revert.
   */
  public function testContentEntityRevisionsRevert() {
    // Revert first revision.
    /** @var \Drupal\node\NodeInterface $node_first_revision */
    $node_first_revision = $this->nodeStorage->loadRevision(1);
    $node_first_revision->setNewRevision(TRUE);
    $node_first_revision->save();

    // There is an extra node revision now.
    $this->assertCount(5, $this->nodeStorage->revisionIds($node_first_revision));
    // Since there are 3 metas and 1 node, there should still be be 3 meta
    // relations only.
    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    // Each relation should now have 5 revisions because the node was updated 4
    // times but then the first revision got reverted into another revision.
    $this->assertCount(5, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(5, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(5, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));

    // There should still be 3 entity metas in total.
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());
    // Each entity meta should still have only 4 revisions each because we have
    // not made any changes to their values, we only reverted the node.
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));

    $this->nodeStorage->resetCache();
    $node = $this->nodeStorage->loadRevision(5);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
    // The meta values of the of the newly created node revisions should be the
    // same as the ones of the first revision.
    $this->assertEquals('red', $visual_meta->get('field_color')->value);
    $this->assertFalse($visual_meta->get('status')->value);
    $this->assertEquals('low', $audio_meta->get('field_volume')->value);
    $this->assertFalse($audio_meta->get('status')->value);
    $this->assertEquals('1', $speed_meta->getWrapper()->getGear());
    $this->assertFalse($speed_meta->get('status')->value);

    // Change again a meta value and confirm everything is normal.
    $visual_meta->set('field_color', 'blue');
    $speed_meta->getWrapper()->setGear('2');
    $node->get('emr_entity_metas')->attach($visual_meta);
    $node->get('emr_entity_metas')->attach($speed_meta);
    $node->setNewRevision(TRUE);
    $node->save();

    $node = $this->nodeStorage->loadRevision(6);
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    $this->assertEquals($visual_meta->get('field_color')->value, 'blue');
    $this->assertFalse($visual_meta->get('status')->value);
    $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
    $this->assertFalse($audio_meta->get('status')->value);
    $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
    $this->assertFalse($speed_meta->get('status')->value);

    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());
    $this->assertCount(6, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(6, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(6, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));
    // The entity meta revisions increased for the speed and visual one.
    $this->assertCount(5, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(5, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));
  }

  /**
   * Create a node with changes done through three revisions.
   */
  protected function createNodeWithFourRevisions() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->create([
      'type' => 'entity_meta_multi_example_ct',
      'title' => 'Node test',
      // We keep the node unpublished to test the meta respects the status.
    ]);
    $node->setPublished(FALSE);

    $entity_metas = [];
    $entity_metas[0] = $this->entityMetaStorage->create([
      'bundle' => 'visual',
      'field_color' => 'red',
    ]);
    $entity_metas[1] = $this->entityMetaStorage->create([
      'bundle' => 'audio',
      'field_volume' => 'low',
    ]);
    $entity_metas[2] = $this->entityMetaStorage->create([
      'bundle' => 'speed',
      'field_gear' => '1',
    ]);
    $node->set('emr_entity_metas', $entity_metas);
    $node->save();

    // Second revision - Change node status, audio and volume.
    $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
    $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

    $node->setNewRevision(TRUE);
    $node->setPublished(TRUE);

    $audio_meta->getWrapper()->setVolume('medium');
    $speed_meta->getWrapper()->setGear('2');
    $node->get('emr_entity_metas')->attach($audio_meta);
    $node->get('emr_entity_metas')->attach($speed_meta);
    $node->save();

    // Third revision - Change visual and speed.
    $this->nodeStorage->resetCache();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->load(1);
    $entity_meta_visual = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $entity_meta_speed = $node->get('emr_entity_metas')->getEntityMeta('speed');
    $entity_meta_visual->set('field_color', 'green');
    $entity_meta_speed->getWrapper()->setGear('3');
    $node->get('emr_entity_metas')->attach($entity_meta_visual);
    $node->get('emr_entity_metas')->attach($entity_meta_speed);
    $node->setNewRevision(TRUE);
    $node->setPublished(FALSE);
    $node->save();

    // Fourth revision - Change visual an publish back the node.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->load(1);
    $entity_meta_visual = $node->get('emr_entity_metas')->getEntityMeta('visual');
    $entity_meta_visual->set('field_color', 'red');
    $node->get('emr_entity_metas')->attach($entity_meta_visual);
    $node->setNewRevision(TRUE);
    $node->setPublished(TRUE);
    $node->save();

    // Make the assertions for all the changes.
    foreach ([1, 2, 3, 4] as $revision) {
      $this->assertEntityMetaRevisionValues($revision);
    }

    // There should be 4 node revisions in total.
    $revisions_ids = $this->nodeStorage->revisionIds($node);
    $this->assertCount(4, $revisions_ids);

    // Since there are 3 metas and 1 node, there should be 3 meta relations.
    $this->assertCount(3, $this->entityMetaRelationStorage->loadMultiple());
    // Each relation should have 4 revisions because the node was updated 4
    // times.
    $this->assertCount(4, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(1)));
    $this->assertCount(4, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(2)));
    $this->assertCount(4, $this->entityMetaRelationStorage->revisionIds($this->entityMetaRelationStorage->load(3)));
    // There should be 3 entity metas in total.
    $this->assertCount(3, $this->entityMetaStorage->loadMultiple());

    // Each entity meta should have 4 revisions since we changed the node status
    // twice and the entity metas followed this status change.
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(1)));
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(2)));
    $this->assertCount(4, $this->entityMetaStorage->revisionIds($this->entityMetaStorage->load(3)));
  }

  /**
   * Asserts entity meta values for a given Node revision.
   *
   * @param int $revision
   *   The Node revision ID.
   */
  protected function assertEntityMetaRevisionValues(int $revision): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->nodeStorage->loadRevision($revision);

    switch ($revision) {
      case 1:
        // First revision, unpublished, so all the metas need to respect that.
        $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
        $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
        $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

        $this->assertEquals($visual_meta->get('field_color')->value, 'red');
        $this->assertFalse($visual_meta->get('status')->value);
        $this->assertEquals($audio_meta->get('field_volume')->value, 'low');
        $this->assertFalse($audio_meta->get('status')->value);
        $this->assertEquals($speed_meta->getWrapper()->getGear(), '1');
        $this->assertFalse($speed_meta->get('status')->value);
        break;

      case 2:
        // Second revision of the node became published so the metas should
        // also.
        $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
        $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
        $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
        // The visual meta was not changed but the other two were.
        $this->assertEquals($visual_meta->get('field_color')->value, 'red');
        $this->assertTrue($visual_meta->get('status')->value);
        $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
        $this->assertTrue($audio_meta->get('status')->value);
        $this->assertEquals($speed_meta->getWrapper()->getGear(), '2');
        $this->assertTrue($speed_meta->get('status')->value);
        break;

      case 3:
        // Third revision has changes only on visual and speed, but again got
        // the node unpublished.
        $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
        $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
        $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');
        $this->assertEquals($visual_meta->get('field_color')->value, 'green');
        $this->assertFalse($visual_meta->get('status')->value);
        $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
        $this->assertFalse($audio_meta->get('status')->value);
        $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
        $this->assertFalse($speed_meta->get('status')->value);
        break;

      case 4:
        $audio_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
        $visual_meta = $node->get('emr_entity_metas')->getEntityMeta('visual');
        $speed_meta = $node->get('emr_entity_metas')->getEntityMeta('speed');

        $this->assertEquals($visual_meta->get('field_color')->value, 'red');
        $this->assertTrue($visual_meta->get('status')->value);
        $this->assertEquals($audio_meta->get('field_volume')->value, 'medium');
        $this->assertTrue($audio_meta->get('status')->value);
        $this->assertEquals($speed_meta->getWrapper()->getGear(), '3');
        $this->assertTrue($speed_meta->get('status')->value);
        break;
    }
  }

}
