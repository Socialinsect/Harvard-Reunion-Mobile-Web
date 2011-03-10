{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <div class="author"><a href="{$photo['author']['url']}">{$photo['author']['name']}</a></div>
  <div class="message">{$photo['message']}</div>
  <div class="when smallprint">
    {$photo['when']['delta']} - <a href="{$photo['commentURL']}">Comment</a> - <a href="{$photo['likeURL']}">Like</a>
  </div>
</div>

<div class="photo">
  <img src="{$photo['img']['src']}" />
</div>

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