<?php

namespace Drupal\medienbericht_bulk_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Imports medienberichte in bulk.
 */
class ImportController extends ControllerBase {
  private $field_mapping = [
    'title' => 'title',
    'date' => [
      'key' => 'field_date',
      'modify' => 'parseDate',
    ],
    'url' => [
      'key' => 'field_url',
      'valueKey' => 'uri',
    ],
    'medium' => 'field_medium',
    'content' => [
      'key' => 'body',
      'template' => [
        'format' => 'basic_html',
      ],
    ],
    'images' => [
      'key' => 'field_images',
      'type' => 'image',
      'multiple' => true,
    ],
  ];


  /**
   * Returns the page where the URLs can be pasted.
   *
   * return []
   */
  public function show () {
    $build = [
      '#markup' => 'Paste a list of URLs into this fields and press "Import":<div id="import"></div>',
      '#attached' => ['library' => ['medienbericht_bulk_import/general']],
    ];
    return $build;
  }

  /**
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function import (Request $request): JsonResponse {
    $url = $request->request->get('url');

    $found = $this->getMedienberichteWithURL($url);

    if (sizeof($found)) {
      $result = [
        'found' => $found,
      ];

      return new JsonResponse($result, 200);
    }

    $data = file_get_contents("http://localhost:8080/?url=" . $url);
    $data = json_decode($data, true);

    if ($data['url'] !== $url) {
      $found = $this->getMedienberichteWithURL($data['url']);

      if (sizeof($found)) {
        $result = [
          'found' => $found,
        ];

        return new JsonResponse($result, 200);
      }
    }

    $id = null;
    $result = [
      'data' => $data,
    ];

    if ($data['title'] && $data['date']) {
      $update = [
        'type' => 'article',
      ];

      foreach ($this->field_mapping as $k => $def) {
        if (is_string($def)) {
          $def = [ 'key' => $def ];
        }

        if (array_key_exists($k, $data)) {
          if (!($def['multiple'] ?? false)) {
            $data[$k] = [$data[$k]];
          }

          $update[$def['key']] = [];
          foreach ($data[$k] as $v) {
            $type = $def['type'] ?? 'default';

            if ($def['modify']) {
              switch ($def['modify']) {
                case 'parseDate':
                  $v = substr($v, 0, 10);
                  break;
              }
            }

            if ($type === 'image') {
              $file_info = pathinfo($v['src']);

              // use temporary space and rely on filefield_paths for moving
              $temp_uri = 'public://filefield_paths/' . $file_info['basename'];
              $contents = file_get_contents($v['src']);

              $file_uri = \Drupal::service('file_system')->saveData($contents, $temp_uri);

              $file = File::create([
                'uri' => $temp_uri,
              ]);
              $file->setPermanent();
              $file->save();

              $value = [
                'target_id' => $file->id(),
                'alt' => $v['alt'],
              ];
            } else {
              $value = $def['template'] ?? [];
              $value[$def['valueKey'] ?? 'value'] = $v;
            }

            $update[$def['key']][] = $value;
          }
        }
      }

      $node = Node::create($update);
      $node->save();
      $result['id'] = $node->id();
    } else {
      $params = [];

      foreach ($data as $k => $value) {
        if (!$field_mapping[$k]) { continue; }

        $def = $field_mapping[$k];
        if (is_string($def)) {
          $def = ['key' => $def];
        }

        if (!is_array($value)) {
          $value = [$value];
        }

        foreach ($value as $i => $v) {
          $params[] = "edit[{$def['key']}][widget][{$i}][" + ($def['valueKey'] ?? 'value') + ']=' + urlencode($v);
        }
      }

      $result['prepoluateParams'] = implode('&', $params);
    }

    return new JsonResponse($result, 200);
  }

  function getMedienberichteWithURL ($url) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->condition('field_url.uri', $url, '=')
      ->accessCheck(true);
    $nids = $query->execute();

    return array_values($nids);
  }
}
