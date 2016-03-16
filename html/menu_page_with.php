<p>
    You have Phabricator account linked for your account
</p>
<form method="post" action="//<?php echo filter_input(INPUT_SERVER, "HTTP_HOST") . filter_input(INPUT_SERVER, "REQUEST_URI") ?>">
    <input type="hidden" name="phab_cmd" value="unlink">
    <input type="submit" class="button button-large" value="Remove link">
</form>
