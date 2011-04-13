{extends file="findExtends:modules/$moduleID/templates/facebookContent.tpl"}

{block name="facebookPost"}
  "{$post['message']}"<br/>
  <span class="smallprint"> - {$post['author']['name']}, {$post['when']['delta']}</span>
  {if !$lastPost}<br/>{/if}
{/block}
