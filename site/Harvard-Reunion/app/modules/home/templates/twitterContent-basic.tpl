{extends file="findExtends:modules/$moduleID/templates/twitterContent.tpl"}

{block name="tweetContent"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {if !$lastPost}<br/>{/if}
{/block}
