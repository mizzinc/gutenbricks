<div id="client-experience" class="tab-pane">
  <div class="setting-panel">
    <h3>Client Experience</h3>
    <p>You can fine tune how users can interact with Gutenberg editor. Contact us for any suggestions.</p>
    <table class="form-table" style="width: auto;">
      <tbody>
        <tr>
          <td style="min-width: 150px;vertical-align:top;">
            <label class="bundle-item">
              <b>Text Editing</b>
            </label>
          </td>
          <td>
            <label class="bundle-item">
              <input type="checkbox" name="_gutenbricks_adv_text_fallback" value="1" <?php checked(get_option('_gutenbricks_adv_text_fallback'), "1"); ?> />
              When user deletes the whole text, the content falls back to its original
              <span class="info-icon" title="Suggested by Olli Koskimäki">C</span>
            </label>
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>

    <?php 
       // Print out the settings for all the integrations
       Gutenbricks\Integration::settings('client-experience'); 
    ?>

    <?php submit_button(); ?>
  </div>
</div>
