{extends file="findExtends:modules/$moduleID/templates/facebookContent.tpl"}

{block name="postContent"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  <br/>{if !$lastPost}<br/>{/if}
{/block}
