<?php
settings_fields('gutenbricks_options_group');

$bundles = get_option('_gutenbricks_active_bundles');
$bundles = !empty($bundles) ? $bundles : [];

$patterns = get_option('_gutenbricks_active_wrap_patterns');
$patterns = !empty($patterns) ? $patterns : [];

$args = array(
  'taxonomy' => GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE,
  'hide_empty' => false,
);

// @since 5.3.0: Add post types editor for each bundle
// get all the post types
$post_types_objects = get_post_types(array('public' => true), 'objects');
$post_types = [];
foreach ($post_types_objects as $post_type) {
  $post_types[$post_type->name] = $post_type->label;
}

$terms = get_terms($args);

// filter out terms that don't have any templates
$terms = array_filter($terms, function ($term) {
  $templates = get_posts(
    array(
      'post_type' => GUTENBRICKS_DB_TEMPLATE_SLUG,
      'posts_per_page' => 1,
      'tax_query' => array(
        array(
          'taxonomy' => GUTENBRICKS_DB_TEMPLATE_TAX_BUNDLE,
          'field' => 'slug',
          'terms' => $term->slug,
        ),
      ),
    )
  );
  return !empty($templates);
});

array_unshift($terms, (object) array('slug' => 'default', 'name' => 'Default'));


// get the post types for each bundle
$bundle_post_types = get_option('_gutenbricks_bundle_post_types');
$bundle_post_types = !empty($bundle_post_types) ? json_decode($bundle_post_types, true) : [];
foreach ($terms as $term) {
  $bundle_post_types[$term->slug] = !empty($bundle_post_types[$term->slug]) ? $bundle_post_types[$term->slug] : [];
}

?>

<script>
  window.__gbAvailablePostTypes = <?php echo wp_json_encode($post_types); ?>;
  window.__gbBundlePostTypes = <?php echo wp_json_encode($bundle_post_types); ?>;
</script>


<div id="bundles" class="tab-pane">
  <input type="hidden" name="_gutenbricks_option_saved" value="1" />
  <div class="setting-panel column">

    <div class="setting-panel-left" x-data="__gbSettingState()">
      <div class="version-message"> 
        <i>
          GutenBricks is tested up to Bricks Builder version
          <?php echo Gutenbricks\Bricks_Bridge::$supported_bricks_versions[count(Gutenbricks\Bricks_Bridge::$supported_bricks_versions) - 1]; ?>.
          Please refer to the currently known issues and compatibility notes <a href="https://docs.gutenbricks.com/changelog/known-issues-and-bugs" target="_blank">here</a>.
        </i>
      </div>
      <h3>Blocks</h3>
      <p>Select one or multiple template bundles that you want to turn into Gutenberg
        blocks or wrap as patterns.
        Templates not assigned to any bundle belong to <b>Default</b> bundle or known as group in Gutenberg
        editor.
      </p>
      <?php

      ?>
      <table class="form-table bundle-table" style="width: auto;" collapse="1">
        <thead>
          <tr>
            <th>
              <b>Bundles</b>
            </th>
            <th>
              <b>As Blocks</b>
            </th>
            <th>
              <b>As Patterns</b>
            </th>
            <th>
              <b>Post Types</b>
            </th>
          </tr>

        </thead>
        <tbody>
          <?php
          if (!empty($terms)) {
            foreach ($terms as $term) {
              $term = (array) $term;

              ?>
              <tr>
                <!-- <th scope="row">Gutenberg Blocks</th> -->
                <td style="min-width: 100px">
                  <?php
                  $checked = in_array($term['slug'], $bundles) ? 'checked' : '';
                  if ($term['slug'] == 'default') {
                    echo '<label class="bundle-item"><b>Default</b></label>';
                  } else {
                    echo '<label class="bundle-item">' . $term['name'] . '</label>';
                  }
                  ?>
                </td>
                <td>
                  <?php
                  $checked = in_array($term['slug'], $bundles) ? 'checked' : '';
                  echo '<label class="bundle-item"><input type="checkbox" name="_gutenbricks_active_bundles[]" value="' . $term['slug'] . '" ' . $checked . ' /></label>';
                  ?>
                </td>
                <td>
                  <?php
                  $checked = in_array($term['slug'], $patterns) ? 'checked' : '';
                  echo '<label class="bundle-item"><input type="checkbox" name="_gutenbricks_active_wrap_patterns[]" value="' . $term['slug'] . '" ' . $checked . ' /></label>';
                  ?>
                </td>
                <td>
                  <div x-show="currentPostEditorFor!=='<?php echo $term['slug']; ?>'" class="post-type-tag-wrapper">
                    <span x-show="!gutenbricksBundlePostTypes['<?php echo $term['slug']; ?>']?.length"
                      class="post-type-tag default-value">All Post Types</span>
                    <template x-for="value in gutenbricksBundlePostTypes['<?php echo $term['slug']; ?>']" :key="value">
                      <span x-text="availablePostTypes[value]" class="post-type-tag"></span>
                    </template>
                    <a>
                      <span class="edit-post-type" @click=" currentPostEditorFor='<?php echo $term['slug']; ?>'">Edit</span>
                    </a>
                  </div>
                  <div x-show=" currentPostEditorFor==='<?php echo $term['slug']; ?>'" class="post-type-box">
                    <label>
                      <input type="checkbox" @click="unselectAll('<?php echo $term['slug']; ?>')"
                        :checked="!gutenbricksBundlePostTypes['<?php echo $term['slug']; ?>']?.length">
                      <span>All Post Types</span>
                    </label>
                    <template x-for="(label, value) in availablePostTypes" :key="value">
                      <label>
                        <input type="checkbox" :value="value"
                          x-model="gutenbricksBundlePostTypes['<?php echo $term['slug']; ?>']"
                          :checked="gutenbricksBundlePostTypes['<?php echo $term['slug']; ?>'].includes(value)">
                        <span x-text="label"></span>
                      </label>
                    </template>
                    <p style="margin-top: 1rem;">
                      <a class="button button-primary" @click="currentPostEditorFor=null">Done</a>
                    </p>
                  </div>
                </td>
              </tr>
              <?php
            }
          }
          ?>
        </tbody>
      </table>
      <input type="hidden" name="_gutenbricks_bundle_post_types" x-model="JSON.stringify(gutenbricksBundlePostTypes)" />

      <h3>Default Bundle Settings</h3>
      <div style="margin: 1.5em 0 3em 0;">
        <label for="_gutenbricks_default_bundle_name"><b>Default Bundle Name</b></label>
        <input type="text" name="_gutenbricks_default_bundle_name" id="_gutenbricks_default_bundle_name"
          style="width:100%" value="<?php echo esc_textarea(get_option('_gutenbricks_default_bundle_name')); ?>"
          placeholder="Default" />
      </div>

      <h3>Default Page Template</h3>
      <div style="margin: 1.5em 0 3em 0;">
        <label for="_gutenbricks_default_bundle_name"><b>Default Page Template Name</b></label>
        <input type="text" name="_gutenbricks_default_page_template_name" id="_gutenbricks_default_page_template_name"
          style="width:100%" value="<?php echo esc_textarea(get_option('_gutenbricks_default_page_template_name')); ?>"
          placeholder="<?php echo GUTENBRICKS_DEFAULT_PAGE_TEMPLATE_NAME; ?>" />
      </div>

      <h3>Block Data</h3>
      <div style="margin: 1.5em 0 3em 0;">
        <label for="_gutenbricks_enable_hidden_values">
          <input type="checkbox" name="_gutenbricks_enable_hidden_values" id="gutenbricks_enable_hidden_values"
            value="1" <?php checked(1, get_option('_gutenbricks_enable_hidden_values'), true); ?> />
          Save block content in post
        </label>
        <p class="description">Enable this for <b>WPML</b> and <b>RankMath</b>. This option will save the block content
          within the post content. Note: When enabling or disabling this option, you might encounter a message stating,
          'This block contains unexpected or invalid content.' In that case, please click 'Attempt Block Recovery.'</p>
      </div>

      <input type="hidden" name="_gutenbricks_option_saved" value="1" />
      <?php submit_button(); ?>
    </div>
    <div class="setting-panel-right" id="onboarding-content">

    </div>
  </div>
</div>
</div>