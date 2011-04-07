{extends file="findExtends:modules/$moduleID/templates/twitter.tpl"}

{block name="twitterHeader"}
  <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
    <td valign="top" width="65%"><h2>{$hashtag}</h2></td>
    <td align="right" width="35%">
      <img src="/common/images/button-twitter.gif" alt="twitter" /><a href="{$tweetURL}">tweet</a>
    </td>
  </tr></table>
{/block}

{block name="tweetContent"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {if !$lastPost}<br/>{/if}
{/block}

{block name="twitterFooter"}
  <p class="nonfocal smallprint">
    View tweets for {$hashtag} at <a href="{$twitterURL}">twitter.com</a>
  </p>
{/block}
