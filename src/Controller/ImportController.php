<?php

namespace Drupal\medienbericht_bulk_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

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
    ]
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
            $value = $def['template'] ?? [];
            $value[$def['valueKey'] ?? 'value'] = $v;
            $update[$def['key']][] = $value;
          }
        }
      }

      $node = Node::create($update);
      $node->save();
      $id = $node->id();
    }

    return new JsonResponse([
      'id' => $id,
      'data' => $data,
    ], 200);
  }
}
