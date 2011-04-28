{extends file="findExtends:modules/$moduleID/templates/facebookContent.tpl"}

{block name="postContent"}
  "{$post['message']|escape}"<br/>
  <span class="smallprint"> - {$post['author']['name']|escape}, {$post['when']['delta']}</span>
  <br/>{if !$lastPost}<br/>{/if}
{/block}
