<p>
    You don't have Phabricator account linked for your account
</p>
<form id="phabform" method="post" action="<?php echo wp_login_url(wp_login_url()); ?>">
    <input type="hidden" name="phab_cmd" value="link">
    <input type="submit" class="button button-large" value="Create link">
</form>
