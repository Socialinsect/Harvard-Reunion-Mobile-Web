{extends file="findExtends:common/templates/postdetailContent.tpl"}

{block name="commentContent"}
  "{$comment['message']|escape}"<br/>
  <span class="smallprint"> - {$comment['author']['name']|escape}, {$comment['when']['delta']}</span>
  <br/>{if !$lastComment}<br/>{/if}
{/block}
