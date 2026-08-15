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
    'date' => 'field_date',
    'url' => 'field_url',
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

    $data = file_get_contents("http://localhost:8080/?url=" . $url);
    $data = json_decode($data, true);

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

            if ($type === 'image') {
              $file_info = pathinfo($v['src']);
              copy($v['src'], '/tmp/medienbericht_bulk_import.dat');

              // use temporary space and rely on filefield_paths for moving
              $temp_uri = 'temporary://' . $file_info['basename'];
              file_put_contents($temp_uri, file_get_contents($v['src']));

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
}
