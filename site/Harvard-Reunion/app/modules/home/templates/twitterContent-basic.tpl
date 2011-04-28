{extends file="findExtends:modules/$moduleID/templates/twitterContent.tpl"}

{block name="tweetContent"}
  "{$post['message']|escape}"<br/>
  <span class="smallprint"> - {$post['author']['name']|escape}, {$post['when']['delta']}</span>
  <br/>{if !$lastPost}<br/>{/if}
{/block}
