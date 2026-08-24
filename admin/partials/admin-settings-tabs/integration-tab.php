<div id="integration" class="tab-pane">
  <div class="setting-panel">
    <h3>Integrations</h3>
    <p>Here you can find options to control your integration and options that provide patches for issues.</p>
    <p>You can find the current status of integration <a href="" target="_blank">here.</a></p>

    <table class="form-table" style="width: auto;">
      <tbody>
        <tr>
          <td style="min-width: 150px;vertical-align:top;">
            <label class="bundle-item">
              <b>Bricks Builder</b>
            </label>
          </td>
          <td>
            <label class="bundle-item">
              <input type="checkbox" name="_gutenbricks_fouc_fix" value="1" <?php checked(get_option('_gutenbricks_fouc_fix'), "1"); ?> />
              Bricks Builder >= 1.9.8 - Add delayed visibility to remove FOUC and CLS issue caused by CSS being rendered in the footer.
            </label>
          </td>
          <td>
          </td>
        </tr>
        <?php 
          do_action('gutenbricks/integration/render_options');
        ?> 

      </tbody>
    </table>
    <?php submit_button(); ?>

  </div>
</div>