{extends file="findExtends:common/templates/postdetailContent.tpl"}

{block name="commentContent"}
  "{$comment['message']}"<br/>
  <span class="smallprint"> - {$comment['author']['name']}, {$comment['when']['delta']}</span>
  <br/>{if !$lastComment}<br/>{/if}
{/block}
