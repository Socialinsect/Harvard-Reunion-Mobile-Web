{include file="findExtends:common/templates/postdetailContent.tpl"}

{block name="comment"}
  "{$comment['message']}"
  <br/>
  <span class="smallprint"> - {$comment['author']['name']}, {$comment['when']['delta']}</span>
  <br/>
{/block}
