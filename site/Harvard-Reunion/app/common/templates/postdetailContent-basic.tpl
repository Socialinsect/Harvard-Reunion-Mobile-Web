{extends file="findExtends:common/templates/postdetailContent.tpl"}

{block name="commentContent"}
  <img src="{$comment['author']['photo']}" height="24" width="24" /> 
  "{$comment['message']|escape}"<br/>
  <span class="smallprint"> - {$comment['author']['name']|escape}, {$comment['when']['delta']}</span>
  <br/>{if !$lastComment}<br/>{/if}
{/block}
