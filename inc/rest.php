<?php
if (!defined('ABSPATH')) exit;

/**
 * REST endpoints
 */
add_action('rest_api_init', function () {
  register_rest_route('ai-layout/v1', '/generate', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_generate',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'input' => [
        'required' => true,
        'type' => 'object',
        'sanitize_callback' => 'ai_layout_sanitize_input'
      ],
      'title' => [
        'required' => false,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
      ],
      'comment' => [
        'required' => false,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field'
      ]
    ]
  ]);
  
  register_rest_route('ai-layout/v1', '/compile', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_compile',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'wireframe' => [
        'required' => true,
        'type' => 'object'
      ]
    ]
  ]);
  
  register_rest_route('ai-layout/v1', '/download', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_download_layout',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'layout' => [
        'required' => true,
        'type' => 'object'
      ]
    ]
  ]);
  
  register_rest_route('ai-layout/v1', '/regenerate', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_regenerate_unlocked',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'wireframe' => [
        'required' => true,
        'type' => 'object'
      ]
    ]
  ]);
  
  register_rest_route('ai-layout/v1', '/apply', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_apply_to_page',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'post_id' => [
        'required' => true,
        'type' => 'integer',
        'validate_callback' => function($param) {
          return get_post($param) !== null;
        }
      ],
      'layout' => [
        'required' => true,
        'type' => 'object'
      ]
    ]
  ]);
  
  register_rest_route('ai-layout/v1', '/save-to-library', [
    'methods'  => 'POST',
    'callback' => 'ai_layout_save_to_library',
    'permission_callback' => function(){ 
      if (!current_user_can('edit_theme_options') || !wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest')) {
        return false;
      }
      return ai_layout_check_rate_limit();
    },
    'args' => [
      'layout' => [
        'required' => true,
        'type' => 'object'
      ]
    ]
  ]);
});

/**
 * Settings helpers
 */
function ai_layout_setting($key, $default = ''){
  $map = [
    'openai_key' => 'ai_layout_openai_api_key',
    'openai_model' => 'ai_layout_model',
    'unsplash_key' => 'ai_layout_unsplash_access_key',
    'pexels_key' => 'ai_layout_pexels_api_key',
  ];
  $opt = isset($map[$key]) ? $map[$key] : $key;
  return get_option($opt, $default);
}

/**
 * Sanitize input data
 */
function ai_layout_sanitize_input($input) {
  if (!is_array($input)) {
    return [];
  }
  
  $sanitized = [];
  
  if (isset($input['url'])) {
    $sanitized['url'] = esc_url_raw($input['url']);
  }
  
  if (isset($input['text'])) {
    $sanitized['text'] = wp_kses_post($input['text']);
  }
  
  return $sanitized;
}

/**
 * Validate wireframe data structure
 */
function ai_layout_validate_wireframe($wireframe) {
  if (!is_array($wireframe)) {
    return false;
  }
  
  // Check required fields
  if (empty($wireframe['sections']) || !is_array($wireframe['sections'])) {
    return false;
  }
  
  // Validate sections structure
  foreach ($wireframe['sections'] as $section) {
    if (!is_array($section) || empty($section['name']) || !isset($section['components'])) {
      return false;
    }
    
    if (!is_array($section['components'])) {
      return false;
    }
    
    // Validate components
    foreach ($section['components'] as $component) {
      if (!is_array($component) || empty($component['type'])) {
        return false;
      }
    }
  }
  
  return true;
}

/**
 * Log errors for debugging
 */
function ai_layout_log_error($message, $context = []) {
  if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('AI Layout Error: ' . $message . ' Context: ' . json_encode($context));
  }
}

/**
 * Rate limiting to prevent abuse
 */
function ai_layout_check_rate_limit($user_id = null) {
  if (!$user_id) {
    $user_id = get_current_user_id();
  }
  
  $transient_key = 'ai_layout_rate_limit_' . $user_id;
  $current_count = get_transient($transient_key);
  
  if ($current_count === false) {
    set_transient($transient_key, 1, HOUR_IN_SECONDS);
    return true;
  }
  
  if ($current_count >= AI_LAYOUT_RATE_LIMIT) { // Max requests per hour
    return false;
  }
  
  set_transient($transient_key, $current_count + 1, HOUR_IN_SECONDS);
  return true;
}

/**
 * OpenAI call (Responses API)
 */
function ai_layout_openai_call($system_prompt, $user_prompt){
  $key = ai_layout_setting('openai_key', '');
  if (empty($key)) {
    ai_layout_log_error('OpenAI API key missing');
    return new WP_Error('no_openai', 'OpenAI API key is missing');
  }

  $model = ai_layout_setting('openai_model', 'gpt-4.1-mini');
  $endpoint = 'https://api.openai.com/v1/responses';

  $input_text = "SYSTEM:
" . $system_prompt . "

USER:
" . $user_prompt;

  $body = [
    'model' => $model,
    'input' => $input_text,
    'text'  => [ 'format' => 'json_object' ]
  ];

  $json_body = wp_json_encode($body);
  if ($json_body === false) {
    ai_layout_log_error('Failed to encode OpenAI request body');
    return new WP_Error('json_error', 'Failed to encode request');
  }

  $args = [
    'headers' => [
      'Authorization' => 'Bearer ' . $key,
      'Content-Type'  => 'application/json'
    ],
    'timeout' => AI_LAYOUT_API_TIMEOUT,
    'body' => $json_body
  ];
  
  $res = wp_remote_post($endpoint, $args);
  if (is_wp_error($res)) {
    ai_layout_log_error('OpenAI API request failed', ['error' => $res->get_error_message()]);
    return $res;
  }
  
  $code = wp_remote_retrieve_response_code($res);
  $raw = wp_remote_retrieve_body($res);
  
  if ($code < 200 || $code >= 300){
    ai_layout_log_error('OpenAI API error response', ['code' => $code, 'response' => $raw]);
    return new WP_Error('openai_error', 'OpenAI error (HTTP ' . $code . '): ' . substr($raw, 0, 200));
  }
  
  $json = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    ai_layout_log_error('Failed to decode OpenAI response', ['json_error' => json_last_error_msg()]);
    return new WP_Error('json_decode_error', 'Failed to decode OpenAI response');
  }
  
  if (!empty($json['output_text'])) {
    return $json['output_text'];
  }
  
  ai_layout_log_error('OpenAI response missing output_text', ['response' => $json]);
  return new WP_Error('openai_invalid_response', 'OpenAI response missing expected output_text field');
}

/**
 * Unsplash / Pexels lookup by keywords
 */
function ai_layout_image_lookup($keywords = []){
  $q = is_array($keywords) ? implode(' ', $keywords) : (string)$keywords;
  $unsplash = ai_layout_setting('unsplash_key', '');
  $pexels   = ai_layout_setting('pexels_key', '');

  // Try Unsplash first
  if (!empty($unsplash)){
    $url = add_query_arg(['query'=>urlencode($q),'per_page'=>1], 'https://api.unsplash.com/search/photos');
    $args = ['headers'=>['Authorization'=>'Client-ID '.$unsplash], 'timeout'=>20];
    $res = wp_remote_get($url, $args);
    
    if (is_wp_error($res)) {
      ai_layout_log_error('Unsplash API request failed', ['error' => $res->get_error_message(), 'query' => $q]);
    } elseif (wp_remote_retrieve_response_code($res) !== 200) {
      ai_layout_log_error('Unsplash API error response', ['code' => wp_remote_retrieve_response_code($res), 'response' => wp_remote_retrieve_body($res)]);
    } else {
      $data = json_decode(wp_remote_retrieve_body($res), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        ai_layout_log_error('Failed to decode Unsplash response', ['json_error' => json_last_error_msg()]);
      } elseif (!empty($data['results'][0]['urls']['regular'])) {
        return $data['results'][0]['urls']['regular'];
      }
    }
  }
  
  // Fallback to Pexels
  if (!empty($pexels)){
    $url = add_query_arg(['query'=>$q, 'per_page'=>1], 'https://api.pexels.com/v1/search');
    $args = ['headers'=>['Authorization'=>$pexels], 'timeout'=>20];
    $res = wp_remote_get($url, $args);
    
    if (is_wp_error($res)) {
      ai_layout_log_error('Pexels API request failed', ['error' => $res->get_error_message(), 'query' => $q]);
    } elseif (wp_remote_retrieve_response_code($res) !== 200) {
      ai_layout_log_error('Pexels API error response', ['code' => wp_remote_retrieve_response_code($res), 'response' => wp_remote_retrieve_body($res)]);
    } else {
      $data = json_decode(wp_remote_retrieve_body($res), true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        ai_layout_log_error('Failed to decode Pexels response', ['json_error' => json_last_error_msg()]);
      } elseif (!empty($data['photos'][0]['src']['large'])) {
        return $data['photos'][0]['src']['large'];
      }
    }
  }
  // Static fallback
  return 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=60';
}

/**
 * Generate (analysis + wireframe + compiled) using OpenAI if configured
 */
function ai_layout_generate(WP_REST_Request $r){
  $payload = $r->get_json_params();
  $input   = isset($payload['input']) ? $payload['input'] : [];
  $title   = isset($payload['title']) ? sanitize_text_field($payload['title']) : 'New Layout';
  $comment = isset($payload['comment']) ? sanitize_text_field($payload['comment']) : '';
  if (empty($input)) return new WP_Error('bad_request', 'Missing input');

  $openai_key = ai_layout_setting('openai_key', '');
  $analysis = [];
  $wireframe = [];

  if (!empty($openai_key)){
    $system = file_get_contents(AI_LAYOUT_PLUGIN_DIR . 'prompts/analysis_prompt.txt');
    $summary = wp_json_encode($input + ['title'=>$title,'comment'=>$comment], JSON_UNESCAPED_SLASHES);
    $out = ai_layout_openai_call($system, 'INPUT_SUMMARY: ' . $summary);
    if (is_wp_error($out)) return $out;
    // Expect two JSON objects; try to parse
    $txt = trim($out);
    // Extract analysis and wireframe JSON blocks
    if (preg_match_all('/\{[\s\S]*\}/m', $txt, $m)){
      // naive: first is analysis, second is wireframe
      $first = json_decode($m[0][0], true);
      $second= isset($m[0][1]) ? json_decode($m[0][1], true) : null;
      if (is_array($first) && isset($first['goals'])){
        $analysis = $first; $wireframe = $second ?: [];
      } else {
        $wireframe = $first; $analysis = $second ?: [];
      }
    }
  }

  if (empty($analysis) || empty($wireframe)){
    // Fallback heuristic (as before)
    $analysis = [
      'goals' => ['clarify value proposition','drive primary CTA clicks'],
      'audience' => ['SMB owners','Ops managers'],
      'key_messages' => ['Save time with automation','Affordable and scalable'],
      'sections' => ['Hero','Features','Social Proof','CTA'],
      'risks' => ['Too much text in hero','Weak contrast on buttons']
    ];
    $wireframe = [
      'page_title' => $title,
      'brand' => [ 'primary_color' => '#1e87f0', 'tone' => 'confident' ],
      'sections' => [
        [
          'name' => 'Hero','intent'=>'communicate USP + CTA',
          'uikit' => ['style'=>'secondary','padding'=>'xlarge','header_transparent'=>true],
          'components' => [
            ['type'=>'headline','copy_hint'=>'Fremtidens Industrielle Automation Starter Her','locked'=>false],
            ['type'=>'text','copy_hint'=>'1-2 linjer der konkretiserer værdien.','locked'=>false],
            ['type'=>'buttons','variants'=>['Få et uforpligtende tilbud','Se hvad vi kan'],'locked'=>false],
            ['type'=>'image','image_keywords'=>['industrial robot','factory'],'placement'=>'right','locked'=>false]
          ]
        ],
        [
          'name' => 'Features','intent'=>'list 3 key benefits',
          'uikit' => ['style'=>'default','padding'=>'large'],
          'components' => [
            ['type'=>'iconlist','items'=>['Hurtig ROI','Skalerbar','Nem integration'],'locked'=>false]
          ]
        ],
        [
          'name'=>'Social Proof','intent'=>'build trust','uikit'=>['style'=>'muted','padding'=>'medium'],
          'components'=>[ ['type'=>'logos','count'=>5,'locked'=>false], ['type'=>'quote','copy_hint'=>'Kort kundeudtalelse','locked'=>false] ]
        ],
        [
          'name'=>'CTA','intent'=>'final conversion','uikit'=>['style'=>'primary','padding'=>'large'],
          'components'=>[ ['type'=>'buttons','variants'=>['Book demo'],'locked'=>false] ]
        ]
      ]
    ];
  }

  // Compile
  $compiled = ai_layout_compile_from_wireframe($wireframe, true);

  return new WP_REST_Response([
    'analysis'  => $analysis,
    'wireframe' => $wireframe,
    'layout'    => $compiled
  ], 200);
}

/**
 * Regenerate only unlocked components (feedback loop)
 */
function ai_layout_regenerate_unlocked(WP_REST_Request $r){
  $payload = $r->get_json_params();
  if (empty($payload['wireframe'])) return new WP_Error('bad_request', 'Missing wireframe');
  $wf = $payload['wireframe'];

  // For now, simply re-run image lookups and replace copy_hints for unlocked items with brief placeholders.
  foreach ($wf['sections'] as &$sec){
    foreach ($sec['components'] as &$c){
      $locked = isset($c['locked']) ? (bool)$c['locked'] : false;
      if ($locked) continue;
      if ($c['type']==='image'){
        $c['src'] = ai_layout_image_lookup($c['image_keywords'] ?? []);
      } elseif ($c['type']==='headline'){
        $c['copy_hint'] = isset($c['copy_hint']) ? $c['copy_hint'] : 'Kort, konkret headline';
      } elseif ($c['type']==='text'){
        $c['copy_hint'] = '1-2 linjer, ingen fluff';
      }
    }
  }
  $layout = ai_layout_compile_from_wireframe($wf, true);
  return new WP_REST_Response(['wireframe'=>$wf,'layout'=>$layout], 200);
}

/**
 * Compile endpoint
 */
function ai_layout_compile(WP_REST_Request $r){
  $payload = $r->get_json_params();
  if (empty($payload['wireframe'])) return new WP_Error('bad_request', 'Missing wireframe');
  
  $wireframe = $payload['wireframe'];
  
  // Validate wireframe structure
  if (!ai_layout_validate_wireframe($wireframe)) {
    return new WP_Error('invalid_wireframe', 'Invalid wireframe structure');
  }
  
  $layout = ai_layout_compile_from_wireframe($wireframe, true);
  return new WP_REST_Response(['layout'=>$layout], 200);
}

/**
 * Mapper: DSL -> YOOtheme JSON
 * If $with_images is true, resolve image_keywords via API
 */
function ai_layout_compile_from_wireframe($wf, $with_images = false){
  $children = [];
  foreach ($wf['sections'] as $sec){
    $row_children = [];
    if (!empty($sec['components'])){
      foreach ($sec['components'] as $c){
        $elt = ai_layout_component_to_element($c, $with_images);
        if ($elt) $row_children[] = $elt;
      }
    }
    $uikit = isset($sec['uikit']) ? $sec['uikit'] : [];
    $section = [
      'name' => $sec['name'],
      'type' => 'section',
      'props'=> array_merge([
        'padding' => isset($uikit['padding']) ? $uikit['padding'] : 'default',
        'style'   => isset($uikit['style']) ? $uikit['style'] : 'default',
      ], array_diff_key($uikit, ['padding'=>1,'style'=>1])),
      'children' => [[
        'type' => 'row',
        'props' => ['margin'=>'medium','margin_remove_bottom'=>true],
        'children' => [[
          'type' => 'column',
          'props'=> ['width'=>'expand'],
          'children' => $row_children
        ]]
      ]]
    ];
    $children[] = $section;
  }
  return [
    'type' => 'layout',
    'version' => '4.5.24',
    'yooessentialsVersion' => '2.4.4',
    'modified' => gmdate('c'),
    'name' => isset($wf['page_title']) ? $wf['page_title'] : 'Generated Layout',
    'children' => $children
  ];
}

function ai_layout_component_to_element($c, $with_images = false){
  $t = $c['type'];
  switch ($t){
    case 'headline':
      return ['type'=>'headline','props'=>['title'=>(string)($c['copy_hint'] ?? 'Overskrift'), 'title_element'=>'h1']];
    case 'text':
      return ['type'=>'text','props'=>['content'=>(string)($c['copy_hint'] ?? 'Tekst…')]];
    case 'buttons':
      $items = [];
      foreach (($c['variants']??['Kontakt']) as $btn){
        $items[] = ['link_text'=>$btn,'link_url'=>'#'];
      }
      return ['type'=>'button','props'=>['items'=>$items,'style'=>'primary','size'=>'large']];
    case 'image':
      $src = isset($c['src']) ? $c['src'] : null;
      if ($with_images && empty($src)){
        $src = ai_layout_image_lookup($c['image_keywords'] ?? []);
      }
      if (empty($src)){
        $src = 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=60';
      }
      $alt = isset($c['alt']) ? $c['alt'] : (is_array($c['image_keywords'] ?? null) ? implode(', ', $c['image_keywords']) : 'image');
      return ['type'=>'image','props'=>['image'=>$src,'alt'=>$alt]];
    case 'iconlist':
      $items = [];
      foreach (($c['items']??[]) as $it){ $items[] = ['content'=>$it]; }
      return ['type'=>'list','props'=>['items'=>$items,'icon'=>'check']];
    case 'logos':
      return ['type'=>'image','props'=>['image'=>'https://dummyimage.com/160x80/ddd/999.png&text=Logos']];
    case 'quote':
      return ['type'=>'text','props'=>['content'=>(string)($c['copy_hint'] ?? '“Kort kundeudtalelse.”')]];
    default:
      return ['type'=>'text','props'=>['content'=>'[Unsupported component: '.$t.']']];
  }
}

/**
 * Download the compiled JSON to uploads and return URL
 */
function ai_layout_download_layout(WP_REST_Request $r){
  $payload = $r->get_json_params();
  if (empty($payload['layout'])) return new WP_Error('bad_request', 'Missing layout');
  
  $layout = $payload['layout'];
  
  // Validate layout structure
  if (!is_array($layout) || !isset($layout['type']) || $layout['type'] !== 'layout') {
    return new WP_Error('invalid_layout', 'Invalid layout structure');
  }
  
  $json = wp_json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false) {
    ai_layout_log_error('Failed to encode layout to JSON');
    return new WP_Error('json_error', 'Failed to encode layout');
  }
  
  $upload_dir = wp_upload_dir();
  $dir = trailingslashit($upload_dir['basedir']) . 'ai-layout';
  
  // Create directory with proper permissions
  if (!file_exists($dir)) {
    $created = wp_mkdir_p($dir);
    if (!$created) {
      ai_layout_log_error('Failed to create upload directory', ['dir' => $dir]);
      return new WP_Error('directory_error', 'Failed to create upload directory');
    }
  }
  
  // Ensure directory is writable
  if (!is_writable($dir)) {
    ai_layout_log_error('Upload directory not writable', ['dir' => $dir]);
    return new WP_Error('permission_error', 'Upload directory not writable');
  }
  
  $filename = 'layout-' . time() . '-' . wp_generate_password(8, false) . '.json';
  $file = trailingslashit($dir) . $filename;
  
  // Write file with error handling
  $bytes_written = file_put_contents($file, $json);
  if ($bytes_written === false) {
    ai_layout_log_error('Failed to write layout file', ['file' => $file]);
    return new WP_Error('write_error', 'Failed to write layout file');
  }
  
  // Set proper file permissions
  chmod($file, 0644);
  
  $url = trailingslashit($upload_dir['baseurl']) . 'ai-layout/' . $filename;
  return new WP_REST_Response(['url'=>$url], 200);
}

/**
 * Apply layout JSON to a given page (post_id) - writes post meta
 * WARNING: Depending on YOOtheme version, meta key may differ.
 */
function ai_layout_apply_to_page(WP_REST_Request $r){
  $payload = $r->get_json_params();
  $post_id = isset($payload['post_id']) ? intval($payload['post_id']) : 0;
  $layout  = isset($payload['layout']) ? $payload['layout'] : null;
  if (!$post_id || empty($layout)) return new WP_Error('bad_request', 'Need post_id and layout');
  $json = wp_json_encode($layout, JSON_UNESCAPED_SLASHES);
  // Common meta keys used by builders; adjust if needed
  update_post_meta($post_id, 'yootheme_builder', $json);
  update_post_meta($post_id, '_yootheme_builder', $json);
  return new WP_REST_Response(['ok'=>true], 200);
}

/**
 * Save to YOOtheme "My Layouts" (best-effort)
 * Stores in an option; if YOOtheme exposes filters, they can be merged.
 */
function ai_layout_save_to_library(WP_REST_Request $r){
  $payload = $r->get_json_params();
  $layout  = isset($payload['layout']) ? $payload['layout'] : null;
  if (empty($layout)) return new WP_Error('bad_request', 'Missing layout');
  $library = get_option('ai_layout_library', []);
  if (!is_array($library)) $library = [];
  $name = isset($layout['name']) ? $layout['name'] : ('Layout '.time());
  $library[$name] = $layout;
  update_option('ai_layout_library', $library, false);
  return new WP_REST_Response(['ok'=>true,'count'=>count($library)], 200);
}
