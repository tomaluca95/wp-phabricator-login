<form id="phabform" method="post" action="<?php echo wp_login_url(wp_login_url()); ?>">
    <input type="hidden" name="phab_cmd" value="login">
    <input type="submit" class="button button-large" value="Login <?php echo get_option("wpphab_label"); ?>">
</form>
