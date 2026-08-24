<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://gutenbricks.com
 * @since      1.0.0
 *
 * @package    Gutenbricks
 * @subpackage Gutenbricks/admin/partials
 */

?>
<div class="gutenbricks-settings">

  <h1>GutenBricks Settings</h1>

  <p>
    Contact & Support
    <a href="mailto:support@gutenbricks.com">support@gutenbricks.com</a>
    | <a href="https://gutenbricks.com/bug-and-issue-report/" target="_blank">Bug Report</a>
    | <a href="https://www.facebook.com/groups/wiredwp" target="_blank">Facebook Group</a>
    (version: <?php echo esc_html($this->version); ?>)
  </p>
  
  <div id="gb-settings-banner"></div>

  <div id="gutenbricks-settings-app"></div>

  <h2 class="nav-tab-wrapper">
    <a href="#bundles" class="nav-tab nav-tab-active" data-tab="bundles">Blocks</a>
    <a href="#gutenberg-settings" class="nav-tab" data-tab="gutenberg-settings">Gutenberg</a>
    <a href="#client-experience" class="nav-tab" data-tab="client-experience">Client Experience</a>
    <a href="#integration" class="nav-tab" data-tab="integration">Integrations</a>
    <a href="#license" class="nav-tab" data-tab="license">License</a>
  </h2>

  <div class="tab-content">
    <form method="post" action="options.php">
      <?php include __DIR__ . '/admin-settings-tabs/bundles-tab.php'; ?>
      <?php include __DIR__ . '/admin-settings-tabs/gutenberg-tab.php'; ?>
      <?php include __DIR__ . '/admin-settings-tabs/client-experience-tab.php'; ?>
      <?php include __DIR__ . '/admin-settings-tabs/integration-tab.php'; ?>
      <?php include __DIR__ . '/admin-settings-tabs/license-tab.php'; ?>
    </form>
  </div>
</div>