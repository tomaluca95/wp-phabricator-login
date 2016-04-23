<form method="post" action="//<?php echo filter_input(INPUT_SERVER, "HTTP_HOST") . filter_input(INPUT_SERVER, "REQUEST_URI") ?>">
    <input type="hidden" name="phab_cmd" value="update">

    <table>
        <tr>
            <th>Auto-register new users</th>
            <td><label><input name="wpphab_auto_register" type="checkbox" value="1" <?php checked( get_option( 'wpphab_auto_register') , 1); ?>></label></td>
        </tr>
        <tr>
            <th>Auto-register new user default role</th>
            <td><input name="wpphab_new_user_role" type="text" value="<?php echo get_option( 'wpphab_new_user_role'); ?>"></td>
        </tr>
        <tr>
            <th>Phabricator URL</th>
            <td><input name="wpphab_phabricator_url" type="text" value="<?php echo get_option( 'wpphab_phabricator_url'); ?>"></td>
        </tr>
        <tr>
            <th>Login label</th>
            <td><input name="wpphab_label" type="text" value="<?php echo get_option( 'wpphab_label'); ?>"></td>
        </tr>
        <tr>
            <th>Delete plugin preferences during uninstall</th>
            <td><label><input name="wpphab_delete_settings_on_uninstall" type="checkbox" value="1" <?php checked( get_option( 'wpphab_delete_settings_on_uninstall') , 1); ?>></label></td>
        </tr>
        <tr>
            <th>Phabricator API ID</th>
            <td><input name="wpphab_api_id" type="text" value="<?php echo get_option( 'wpphab_api_id' ); ?>"></td>
        </tr>
        <tr>
            <th>Phabricator API secret</th>
            <td><input name="wpphab_api_secret" type="text" value="<?php echo get_option( 'wpphab_api_secret' ); ?>"></td>
        </tr>
    </table>

    <input type="submit" class="button button-large" value="Update Values">
</form>
