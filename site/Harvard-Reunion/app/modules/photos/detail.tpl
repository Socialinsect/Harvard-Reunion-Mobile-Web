{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <div class="author"><a href="{$photo['author']['url']}">{$photo['author']['name']}</a></div>
  <div class="message">{$photo['message']}</div>
  <div class="when smallprint">
    {$photo['when']['delta']}
    {foreach $photo['actions'] as $action} 
    &nbsp;-&nbsp;<a href="{$action['link']}">{$action['name']}</a>
    {/foreach}
  </div>
</div>

<img class="largePhoto" src="{$photo['img']}" />

{if count($photo['comments'])}
  {foreach $photo['comments'] as $i => $comment}
    {capture name="title" assign="title"}
      <a class="author" href="{$comment['author']['url']}">{$comment['author']['name']}</a>
      &nbsp;{$comment['message']}
    {/capture}
    {$photo['comments'][$i]['title'] = $title}
    {$photo['comments'][$i]['subtitle'] = $comment['when']['delta']}
  {/foreach}

  {include file="findInclude:common/results.tpl" results=$photo['comments']}
{/if}

{include file="findInclude:common/footer.tpl"}
